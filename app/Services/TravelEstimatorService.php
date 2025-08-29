<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class TravelEstimatorService
{
    /**
     * Estimate travel for an order
     * 1. Take an order as input
     * 2. Get coordinates for customer and every seller using Geoapify
     * 3. Sort sellers by distance to customer (furthest first)
     * 4. Create travel route: furthest seller -> other sellers -> customer
     * 5. Return distance, time, and estimated freight
     */
    public function estimateTravel($order)
    {
        Log::info("🚚 Starting travel estimation for order", [
            'customer_address' => $order->customer->address(),
            'order_id' => $order->id ?? 'mock_order'
        ]);

        // Get all sellers from the order
        $sellers = $this->getSellersWithAddresses($order);
        
        if (empty($sellers)) {
            Log::warning("❌ No sellers found for order");
            return $this->emptyTravelResult();
        }

        Log::info("✅ Found sellers for travel estimation", [
            'sellers_count' => count($sellers),
            'seller_ids' => array_map(function($seller) { return $seller['seller']->id; }, $sellers)
        ]);

        // Get customer address and coordinates
        $customerAddress = $order->customer->address();
        $customerCoords = $this->geocodeAddress($customerAddress);
        
        if (!$customerCoords) {
            Log::error("❌ Failed to geocode customer address: {$customerAddress}");
            return $this->emptyTravelResult();
        }

        Log::info("📍 Customer coordinates obtained", [
            'customer_address' => $customerAddress,
            'latitude' => $customerCoords['lat'],
            'longitude' => $customerCoords['lon']
        ]);

        // Calculate distances from customer to each seller using coordinates
        $sellersWithDistances = [];
        foreach ($sellers as $sellerData) {
            $sellerAddress = $sellerData['address'];
            $sellerCoords = $this->geocodeAddress($sellerAddress);
            
            if (!$sellerCoords) {
                Log::error("❌ Failed to geocode seller address: {$sellerAddress}");
                continue;
            }

            Log::info("📍 Seller coordinates obtained", [
                'seller_id' => $sellerData['seller']->id,
                'seller_address' => $sellerAddress,
                'latitude' => $sellerCoords['lat'],
                'longitude' => $sellerCoords['lon']
            ]);

            // Calculate distance using coordinates
            $distance = $this->calculateDistanceBetweenCoordinates(
                $customerCoords['lat'], $customerCoords['lon'],
                $sellerCoords['lat'], $sellerCoords['lon']
            );

            $sellersWithDistances[] = [
                'seller' => $sellerData['seller'],
                'address' => $sellerAddress,
                'coordinates' => $sellerCoords,
                'distance_to_customer' => $distance,
                'time_to_customer' => $this->estimateTimeFromDistance($distance)
            ];

            Log::info("📏 Distance calculated for seller", [
                'seller_id' => $sellerData['seller']->id,
                'distance_meters' => $distance,
                'estimated_time_seconds' => $this->estimateTimeFromDistance($distance)
            ]);
        }

        if (empty($sellersWithDistances)) {
            Log::error("❌ No sellers with valid coordinates found");
            return $this->emptyTravelResult();
        }

        // Sort sellers by distance to customer (furthest first)
        usort($sellersWithDistances, function ($a, $b) {
            return $b['distance_to_customer'] <=> $a['distance_to_customer'];
        });

        Log::info("📊 Sellers sorted by distance to customer (furthest first)", [
            'sorted_sellers' => array_map(function($seller) {
                return [
                    'seller_id' => $seller['seller']->id,
                    'distance_to_customer_meters' => $seller['distance_to_customer'],
                    'distance_to_customer_km' => round($seller['distance_to_customer'] / 1000, 2)
                ];
            }, $sellersWithDistances)
        ]);

        // Calculate the complete travel route using coordinates
        $travelResult = $this->calculateTravelRouteWithCoordinates($customerCoords, $sellersWithDistances);
        
        $freightCostPerKm = 1.0; // Example cost per kilometer
        $totalFreightCost = ($travelResult['total_distance_meters'] / 1000) * $freightCostPerKm;
        
        Log::info("✅ Travel estimation completed successfully", [
            'total_distance_meters' => $travelResult['total_distance_meters'],
            'total_distance_km' => round($travelResult['total_distance_meters'] / 1000, 2),
            'total_time_seconds' => $travelResult['total_time_seconds'],
            'total_time_hours' => round($travelResult['total_time_seconds'] / 3600, 2),
            'total_freight_cost' => $totalFreightCost,
            'route_order' => array_map(function($seller) { 
                return $seller['seller']->id; 
            }, $travelResult['sellers'])
        ]);
        
        // Prepare sellers array with distance key for AIController compatibility
        $transformedSellers = array_map(function($sellerData) {
            return [
                'seller' => $sellerData['seller'],
                'address' => $sellerData['address'],
                'distance' => $sellerData['distance_to_customer'], // Use distance_to_customer as the distance
                'distance_to_customer' => $sellerData['distance_to_customer'],
                'travel_sequence' => $sellerData['travel_sequence'] ?? null
            ];
        }, $sellersWithDistances);

        return [
            'total_distance_meters' => $travelResult['total_distance_meters'],
            'total_time_seconds' => $travelResult['total_time_seconds'],
            'sellers' => $transformedSellers,
            'total_freight_cost' => $totalFreightCost,
            'optimal_route' => true,
        ];
    }

    /**
     * Get sellers with their addresses from order
     */
    private function getSellersWithAddresses($order)
    {
        $sellers = [];
        foreach ($order->products as $product) {
            $seller = $product->seller;
            if (!isset($sellers[$seller->id])) {
                $sellers[$seller->id] = [
                    'seller' => $seller,
                    'address' => $seller->address(),
                ];
            }
        }
        return array_values($sellers);
    }

    /**
     * Geocode address to coordinates using Geoapify
     */
    private function geocodeAddress($address)
    {
        $apiKey = config('services.geoapify.key');
        if (!$apiKey) {
            Log::error("❌ Geoapify API key not configured");
            return null;
        }

        $url = "https://api.geoapify.com/v1/geocode/search?text=" . urlencode($address) . "&apiKey={$apiKey}";

        Log::info("🌍 Geocoding address", ['address' => $address, 'url' => $url]);

        try {
            $response = Http::timeout(10)->get($url);
            
            if ($response->successful()) {
                $data = $response->json();
                
                if (!empty($data['features'][0]['properties']['lat']) && !empty($data['features'][0]['properties']['lon'])) {
                    $result = [
                        'lat' => $data['features'][0]['properties']['lat'],
                        'lon' => $data['features'][0]['properties']['lon'],
                    ];
                    Log::info("✅ Geocoding successful", $result);
                    return $result;
                } else {
                    Log::error("❌ Geocoding API response missing coordinates", ['data' => $data]);
                }
            } else {
                Log::error("❌ Geocoding API error response", [
                    'status' => $response->status(),
                    'body' => $response->body()
                ]);
            }
        } catch (\Exception $e) {
            Log::error("❌ Geocoding HTTP request failed: " . $e->getMessage());
        }

        return null;
    }

    /**
     * Calculate distance between two coordinates using Haversine formula
     */
    private function calculateDistanceBetweenCoordinates($lat1, $lon1, $lat2, $lon2)
    {
        $earthRadius = 6371000; // Earth's radius in meters

        $lat1 = deg2rad($lat1);
        $lon1 = deg2rad($lon1);
        $lat2 = deg2rad($lat2);
        $lon2 = deg2rad($lon2);

        $deltaLat = $lat2 - $lat1;
        $deltaLon = $lon2 - $lon1;

        $a = sin($deltaLat / 2) * sin($deltaLat / 2) +
             cos($lat1) * cos($lat2) *
             sin($deltaLon / 2) * sin($deltaLon / 2);
        
        $c = 2 * atan2(sqrt($a), sqrt(1 - $a));
        
        $distance = $earthRadius * $c;

        Log::info("📐 Calculated distance between coordinates", [
            'from_lat' => $lat1,
            'from_lon' => $lon1,
            'to_lat' => $lat2,
            'to_lon' => $lon2,
            'distance_meters' => $distance
        ]);

        return $distance;
    }

    /**
     * Estimate time from distance (simplified: 50 km/h average speed)
     */
    private function estimateTimeFromDistance($distanceMeters)
    {
        $speedKmh = 50; // Average speed 50 km/h
        $speedMs = $speedKmh * 1000 / 3600; // Convert to m/s
        
        $timeSeconds = $distanceMeters / $speedMs;
        
        Log::info("⏱️ Estimated time from distance", [
            'distance_meters' => $distanceMeters,
            'speed_kmh' => $speedKmh,
            'estimated_time_seconds' => $timeSeconds
        ]);

        return $timeSeconds;
    }

    /**
     * Calculate travel route using coordinates: furthest seller -> other sellers -> customer
     */
    private function calculateTravelRouteWithCoordinates($customerCoords, $sellersWithDistances)
    {
        $totalDistance = 0;
        $totalTime = 0;
        
        if (empty($sellersWithDistances)) {
            return [
                'total_distance_meters' => 0,
                'total_time_seconds' => 0,
                'sellers' => []
            ];
        }

        Log::info("🗺️ Calculating travel route with coordinates");

        // Travel between sellers (from furthest to closest)
        for ($i = 0; $i < count($sellersWithDistances) - 1; $i++) {
            $fromSeller = $sellersWithDistances[$i];
            $toSeller = $sellersWithDistances[$i + 1];
            
            $legDistance = $this->calculateDistanceBetweenCoordinates(
                $fromSeller['coordinates']['lat'], $fromSeller['coordinates']['lon'],
                $toSeller['coordinates']['lat'], $toSeller['coordinates']['lon']
            );
            
            $totalDistance += $legDistance;
            $totalTime += $this->estimateTimeFromDistance($legDistance);
            
            Log::info("↔️ Travel leg between sellers", [
                'from_seller_id' => $fromSeller['seller']->id,
                'to_seller_id' => $toSeller['seller']->id,
                'distance_meters' => $legDistance,
                'cumulative_distance' => $totalDistance
            ]);
        }

        // Travel from closest seller to customer
        $closestSeller = end($sellersWithDistances);
        $finalLegDistance = $this->calculateDistanceBetweenCoordinates(
            $closestSeller['coordinates']['lat'], $closestSeller['coordinates']['lon'],
            $customerCoords['lat'], $customerCoords['lon']
        );
        
        $totalDistance += $finalLegDistance;
        $totalTime += $this->estimateTimeFromDistance($finalLegDistance);

        Log::info("🏁 Final leg: Closest seller to customer", [
            'seller_id' => $closestSeller['seller']->id,
            'distance_meters' => $finalLegDistance,
            'cumulative_distance' => $totalDistance
        ]);

        return [
            'total_distance_meters' => $totalDistance,
            'total_time_seconds' => $totalTime,
            'sellers' => $sellersWithDistances
        ];
    }

    /**
     * Return empty travel result
     */
    private function emptyTravelResult()
    {
        Log::warning("📦 Returning empty travel result");
        
        return [
            'total_distance_meters' => 0,
            'total_time_seconds' => 0,
            'sellers' => [],
            'total_freight_cost' => 0,
            'optimal_route' => false,
        ];
    }
}
