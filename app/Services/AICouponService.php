<?php

namespace App\Services;

use App\Models\Order;
use App\Models\Coupon;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use App\Services\TravelEstimatorService;
use App\Services\CouponService;

class AICouponService
{
    /**
     * Predict customer satisfaction and create coupon if dissatisfied.
     *
     * @param Order $order
     * @param float|null $freightCost
     * @param float|null $deliveryTimeHours
     * @return array
     */
    public function predictAndHandleCoupon(Order $order, ?float $freightCost = null, ?float $deliveryTimeHours = null): array
    {
        try {
            // Prepare mock order data for AI prediction
            $mockOrder = new \stdClass();
            $mockOrder->customer = $order->customer;
            $mockOrder->products = $order->items ? collect($order->items)->map(function ($item) {
                return \App\Models\Product::find($item['product_id']);
            }) : collect();
            $mockOrder->total_amount = $order->total_amount;
            $mockOrder->total_items = $order->getTotalItemsAttribute();

            // Use TravelEstimatorService to calculate freight and delivery time if not provided
            if ($freightCost === null || $deliveryTimeHours === null) {
                $travelEstimator = new TravelEstimatorService();
                $freightData = $travelEstimator->estimateTravel($mockOrder);
                $freightCost = $freightData['total_freight_cost'] ?? 0;
                $deliveryTimeHours = $freightData['total_time_hours'] ?? 0;
            }

            // Prepare data for AI prediction
            $predictionData = [
                'total_price' => (float) $mockOrder->total_amount,
                'total_items' => $mockOrder->total_items,
                'total_payment' => (float) $mockOrder->total_amount,
                'payment_count' => 1,
                'distance' => $freightCost,
                'delivery_time' => $deliveryTimeHours,
                'product_category_name' => $this->getDominantCategory($mockOrder->products),
            ];

            // Send prediction request to AI service
            $response = Http::timeout(10)->post('http://fastapi:8000/predict', $predictionData);

            if ($response->successful()) {
                $result = $response->json();
                $prediction = $result['data']['prediction'] ?? null;

                if ($prediction === 0) { // Dissatisfied customer predicted
                    // Create coupon for reduction
                    $couponService = new CouponService();
                    $coupon = $couponService->createCouponForUser($order->customer);

                    return [
                        'coupon_generated' => true,
                        'coupon' => $coupon,
                        'reason' => 'AI prediction indicated dissatisfaction',
                    ];
                }
            } else {
                Log::warning('AI service prediction failed: ' . $response->body());
            }
        } catch (\Exception $e) {
            Log::error('AI coupon prediction error: ' . $e->getMessage());
        }

        return [
            'coupon_generated' => false,
            'coupon' => null,
            'reason' => 'No coupon generated',
        ];
    }

    /**
     * Predict customer satisfaction and potentially generate coupon for a mock order (used in order creation view).
     *
     * @param mixed $mockOrder
     * @param float|null $freightCost
     * @return array
     */
    public function predictAndGenerateCoupon($mockOrder, ?float $freightCost = null): array
    {
        try {
            // Prepare data for AI prediction
            $predictionData = [
                'total_price' => (float) $mockOrder->total_amount,
                'total_items' => $mockOrder->total_items,
                'total_payment' => (float) $mockOrder->total_amount,
                'payment_count' => 1,
                'distance' => $freightCost,
                'delivery_time' => 0, // Default for mock orders
                'product_category_name' => $this->getDominantCategory($mockOrder->products),
            ];

            // Send prediction request to AI service
            $response = Http::timeout(10)->post('http://fastapi:8000/predict', $predictionData);

            if ($response->successful()) {
                $result = $response->json();
                $prediction = $result['data']['prediction'] ?? null;

                if ($prediction === 0) { // Dissatisfied customer predicted
                    // Create coupon for reduction
                    $couponService = new CouponService();
                    $coupon = $couponService->createCouponForUser($mockOrder->customer);

                    return [
                        'coupon_generated' => true,
                        'coupon' => $coupon,
                        'prediction' => $prediction,
                        'confidence' => $result['data']['confidence'] ?? null,
                        'reason' => 'AI prediction indicated dissatisfaction',
                    ];
                }

                return [
                    'coupon_generated' => false,
                    'coupon' => null,
                    'prediction' => $prediction,
                    'confidence' => $result['data']['confidence'] ?? null,
                    'reason' => 'No coupon generated - customer satisfaction predicted',
                ];
            } else {
                Log::warning('AI service prediction failed: ' . $response->body());
            }
        } catch (\Exception $e) {
            Log::error('AI coupon prediction error: ' . $e->getMessage());
        }

        return [
            'coupon_generated' => false,
            'coupon' => null,
            'prediction' => null,
            'confidence' => null,
            'reason' => 'No coupon generated - prediction failed',
        ];
    }

    /**
     * Get dominant product category from products collection.
     *
     * @param \Illuminate\Support\Collection $products
     * @return string
     */
    private function getDominantCategory($products): string
    {
        $categories = $products->groupBy('category')->map->count();
        $dominantCategory = $categories->sortDesc()->keys()->first();

        return $dominantCategory ?? 'unknown';
    }
}
