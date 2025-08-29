<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

// Input of order. 

// Fetching all products in the order

// Fetching all differents sellers in the order

// For each seller, fetching their address and calculating the distance to the customer

// create best initinary for freight by seller distance (from furthest to closest)

// calculate and return total freight cost, distance and time

class TravelEstimator
{
    public function getSellersByDistance($order)
    {
        $sellers = [];
        foreach ($order->products as $product) {
            $seller = $product->seller;
            if (!isset($sellers[$seller->id])) {
                $sellers[$seller->id] = [
                    'seller' => $seller,
                    'address' => $seller->address,
                    'distance' => null,
                ];
            }
        }
        
        foreach ($sellers as &$sellerData) {
            $distanceData = $this->calculateDistance($order->customer->address, $sellerData['address']);
            if ($distanceData['success']) {
                $sellerData['distance'] = $distanceData['distance'];
            } else {
                Log::error("Failed to calculate distance for seller ID {$sellerData['seller']->id}");
            }
        }
        unset($sellerData); // Good practice to break the reference

        usort($sellers, function ($a, $b) {
            return $b['distance'] <=> $a['distance'];
        });

        return $sellers;
    }

    public function calculateDistance($origin, $destination)
    {
        $apiKey = config('services.geoapify.key');
        $url = "https://api.geoapify.com/v1/routing?waypoints=" . urlencode($origin) . "|" . urlencode($destination) . "&mode=drive&apiKey={$apiKey}";

        try {
            $response = Http::get($url);
            if ($response->successful()) {
                $data = $response->json();
                if (isset($data['results'][0]['properties']['distance']) && isset($data['results'][0]['properties']['time'])) {
                    return [
                        'success' => true,
                        'distance' => $data['results'][0]['properties']['distance'], // in meters
                        'time' => $data['results'][0]['properties']['time'], // in seconds
                    ];
                }
            }
            Log::error("Geoapify API error: " . $response->body());
        } catch (\Exception $e) {
            Log::error("HTTP request failed: " . $e->getMessage());
        }

        return ['success' => false];
    }

    public function estimateTravel($order)
    {
        $sellers = $this->getSellersByDistance($order);
        $totalDistance = 0;
        $totalTime = 0;

        // Go through sellers 

        // Calculate distance from customer to first seller, then from first seller to second, etc.

        // Finally add all distances and times
        $previousLocation = $order->customer->address;
        foreach ($sellers as &$sellerData) {
            if ($sellerData['distance'] === null) {
                continue; // Skip if distance calculation failed
            }
            $distanceData = $this->calculateDistance($previousLocation, $sellerData['address']);
            if ($distanceData['success']) {
                $totalDistance += $distanceData['distance'];
                $totalTime += $distanceData['time'];
                $previousLocation = $sellerData['address'];
            } else {
                Log::error("Failed to calculate leg distance for seller ID {$sellerData['seller']->id}");
            }
        }
        $freightCostPerKm = 1.0; // Example cost per kilometer
        $totalFreightCost = ($totalDistance / 1000) * $freightCostPerKm;
        return [
            'total_distance_meters' => $totalDistance,
            'total_time_seconds' => $totalTime,
            'sellers' => $sellers,
            'total_freight_cost' => $totalFreightCost,
        ];
    }
}