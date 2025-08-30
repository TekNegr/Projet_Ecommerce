<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use App\Models\Order;
use App\Models\Product;
use App\Models\User;
use Illuminate\Support\Facades\Log;
use App\Services\TravelEstimatorService;

class AIController extends Controller
{
    // Controller to interact with the Python FastAPI service in order to communicate with the AI model 
    public function health()
    {
        try{
            $response = Http::get('http://fastapi:8000/health');
            if ($response->successful()) {
                return response()->json([
                    'status' => 'connected',
                    'python_service' => $response->json(),
                    'message' => 'Python service is connected and healthy',
                ]);
            } else {
                return response()->json([
                    'status' => 'disconnected',
                    'message' => 'Python service is not connected',
                    'details' => $response->body()
                ], 500);
            }
        }
        catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to connect to Python service',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Display the AI dashboard
     */
    public function dashboard()
    {
        $healthStatus = $this->getHealthStatus();
        
        // Get all users (not just customers) and products for the testing interface
        $users = User::all();
        $products = Product::where('status', 'active')->get();
        
        return view('admin.ai-dashboard', [
            'healthStatus' => $healthStatus,
            'users' => $users,
            'products' => $products
        ]);
    }

    /**
     * Get the health status of the AI service
     */
    private function getHealthStatus()
    {
        try {
            $response = Http::timeout(5)->get('http://fastapi:8000/health');
            
            if ($response->successful()) {
                return [
                    'status' => 'connected',
                    'message' => 'AI service is connected and healthy',
                    'details' => $response->json()
                ];
            } else {
                return [
                    'status' => 'disconnected',
                    'message' => 'AI service is not responding properly',
                    'details' => $response->body()
                ];
            }
        } catch (\Exception $e) {
            return [
                'status' => 'error',
                'message' => 'Failed to connect to AI service',
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * Get prediction for a single order
     */
    public function predictOrder(Request $request, $orderId = null)
    {
        try {
            // Get order data
            $order = $orderId ? Order::findOrFail($orderId) : $this->getOrderFromRequest($request);
            
            // Prepare data for AI model
            $predictionData = $this->preparePredictionData($order);
            
            // Send prediction request to AI service
            $response = Http::timeout(10)->post('http://fastapi:8000/predict', $predictionData);
            
            if ($response->successful()) {
                $result = $response->json();
                
                return response()->json([
                    'status' => 'success',
                    'message' => 'Prediction successful',
                    'order_id' => $order->id,
                    'prediction' => $result['data']['prediction'],
                    'confidence' => $result['data']['confidence'],
                    'interpretation' => $this->interpretPrediction($result['data']['prediction']),
                    'raw_data' => $predictionData
                ]);
            } else {
                return response()->json([
                    'status' => 'error',
                    'message' => 'AI service prediction failed',
                    'details' => $response->body(),
                    'order_id' => $order->id
                ], 500);
            }
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to get prediction',
                'error' => $e->getMessage(),
                'order_id' => $orderId ?? 'unknown'
            ], 500);
        }
    }

    /**
     * Calculate freight and get prediction for pseudo-order
     */
    public function calculatePseudoOrder(Request $request)
    {
        try {
            $request->validate([
                'customer_id' => 'required|exists:users,id',
                'product_ids' => 'required|array',
                'product_ids.*' => 'exists:products,id',
                'freight_cost_per_km' => 'nullable|numeric|min:0.01|max:10.00'
            ]);

            $customer = User::findOrFail($request->customer_id);
            $products = Product::whereIn('id', $request->product_ids)->get();
            
            // Get freight cost per km from request or use default
            $freightCostPerKm = $request->input('freight_cost_per_km', 0.1);

            if ($products->isEmpty()) {
                return back()->withErrors(['message' => 'No valid products selected']);
            }

            // Calculate order totals
            $totalAmount = $products->sum('price');
            $totalItems = $products->count();

            // Get sellers from products
            $sellers = $products->groupBy('user_id')->map(function ($sellerProducts) {
                return [
                    'seller' => $sellerProducts->first()->seller(),
                    'products' => $sellerProducts,
                    'subtotal' => $sellerProducts->sum('price')
                ];
            });

            // Create a mock order object for TravelEstimatorService
            $mockOrder = new \stdClass();
            $mockOrder->customer = $customer;
            $mockOrder->products = $products;
            $mockOrder->total_amount = $totalAmount;
            $mockOrder->total_items = $totalItems;

            // Use TravelEstimatorService to calculate freight with custom cost per km
            $travelEstimator = new TravelEstimatorService();
            $freightData = $travelEstimator->estimateTravel($mockOrder, $freightCostPerKm);

            // Convert to appropriate units for AI prediction and round them
            $totalDistanceKm = round($freightData['total_distance_meters'] / 1000, 2);
            $totalTimeHours = round($freightData['total_time_seconds'] / 3600, 2);

            // Prepare prediction data
            $predictionData = $this->preparePredictionData(
                $mockOrder,
                $totalDistanceKm,
                $totalTimeHours
            );

            // Get prediction from AI service with better error handling
            try {
                $predictionResponse = Http::timeout(10)->post('http://fastapi:8000/predict', $predictionData);
                
                if ($predictionResponse->successful()) {
                    $predictionResult = $predictionResponse->json();
                    $prediction = [
                        'score' => $predictionResult['data']['prediction'],
                        'confidence' => $predictionResult['data']['confidence'],
                        'interpretation' => $this->interpretPrediction($predictionResult['data']['prediction']),
                        'input_data' => $predictionData
                    ];
                } else {
                    $prediction = null;
                    Log::warning('AI service returned error: ' . $predictionResponse->body());
                }
            } catch (\Exception $e) {
                $prediction = null;
                Log::error('AI service connection failed: ' . $e->getMessage());
            }

            // Get health status for the view
            $healthStatus = $this->getHealthStatus();
            $users = User::all();
            
            // Prepare seller information
            $sellerInfo = collect($freightData['sellers'])->map(function ($sellerData) {
                return [
                    'id' => $sellerData['seller']->id,
                    'name' => $sellerData['seller']->name,
                    'address' => $sellerData['seller']->address(),
                    'distance_km' => $sellerData['distance'] ? $sellerData['distance'] / 1000 : null,
                    'products_count' => count($sellerData['seller']->products ?? [])
                ];
            });
            
            return view('admin.ai-dashboard', [
                'healthStatus' => $healthStatus,
                'users' => $users,
                'products' => Product::where('status', 'active')->get(),
                'calculationResult' => [
                    'customer' => $customer,
                    'products' => $products,
                    'sellers' => $sellerInfo,
                    'totals' => [
                        'subtotal' => $totalAmount,
                        'freight_cost' => $freightData['total_freight_cost'],
                        'total_with_freight' => $totalAmount + $freightData['total_freight_cost'],
                        'total_items' => $totalItems
                    ],
                    'delivery' => [
                        'total_distance_km' => $totalDistanceKm,
                        'total_time_hours' => $totalTimeHours,
                        'sellers_count' => count($freightData['sellers'])
                    ],
                    'prediction' => $prediction
                ]
            ]);

        } catch (\Exception $e) {
            Log::error('Pseudo-order calculation failed: ' . $e->getMessage());
            return back()->withErrors(['message' => 'Failed to calculate pseudo-order: ' . $e->getMessage()]);
        }
    }

    /**
     * Estimate freight for sellers
     */
    private function estimateFreightForSellers($customer, $sellers)
    {
        // For demo purposes, we'll use simplified calculations
        // In real implementation, use TravelEstimatorService
        
        $totalDistanceKm = 0;
        $totalTimeHours = 0;
        $freightCostPerKm = 1.0; // $1 per km
        
        // Calculate based on number of sellers (simplified)
        $sellersCount = $sellers->count();
        $baseDistance = 5; // base 5km per seller
        $baseTime = 0.5; // base 0.5 hours per seller
        
        $totalDistanceKm = $baseDistance * $sellersCount;
        $totalTimeHours = $baseTime * $sellersCount;
        $totalFreightCost = $totalDistanceKm * $freightCostPerKm;
        
        return [
            'total_distance_km' => $totalDistanceKm,
            'total_time_hours' => $totalTimeHours,
            'total_freight_cost' => $totalFreightCost
        ];
    }

    /**
     * Get dominant product category
     */
    private function getDominantCategory($products)
    {
        $categories = $products->groupBy('category')->map->count();
        $dominantCategory = $categories->sortDesc()->keys()->first();
        
        return $dominantCategory ?? 'unknown';
    }

    /**
     * Get batch predictions for multiple orders
     */
    public function batchPredict(Request $request)
    {
        try {
            $orderIds = $request->input('order_ids', []);
            
            if (empty($orderIds)) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'No order IDs provided'
                ], 400);
            }
            
            $orders = Order::whereIn('id', $orderIds)->get();
            
            if ($orders->isEmpty()) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'No orders found with the provided IDs'
                ], 404);
            }
            
            // Prepare batch data
            $batchData = [];
            foreach ($orders as $order) {
                $batchData[] = $this->preparePredictionData($order);
            }
            
            // Send batch prediction request
            $response = Http::timeout(30)->post('http://fastapi:8000/batch_predict', $batchData);
            
            if ($response->successful()) {
                $results = $response->json();
                
                $predictions = [];
                foreach ($orders as $index => $order) {
                    if (isset($results['data'][$index])) {
                        $predictionData = $results['data'][$index];
                        $predictions[] = [
                            'order_id' => $order->id,
                            'prediction' => $predictionData['prediction'],
                            'confidence' => $predictionData['confidence'],
                            'interpretation' => $this->interpretPrediction($predictionData['prediction'])
                        ];
                    }
                }
                
                return response()->json([
                    'status'=> 'success',
                    'message' => 'Batch prediction successful',
                    'total_orders' => count($orders),
                    'successful_predictions' => count($predictions),
                    'predictions' => $predictions
                ]);
            } else {
                return response()->json([
                    'status' => 'error',
                    'message' => 'AI service batch prediction failed',
                    'details' => $response->body()
                ], 500);
            }
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to get batch predictions',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get model information
     */
    public function getModelInfo()
    {
        try {
            // For now, return static info since the AI service doesn't have a model info endpoint
            // You can extend the Python API to include model information if needed
            return response()->json([
                'status' => 'success',
                'message' => 'Model information retrieved',
                'data' => [
                    'model_type' => 'Random Forest Classifier',
                    'service' => 'FastAPI AI Service',
                    'version' => '1.0.0',
                    'endpoints' => [
                        'health' => 'http://fastapi:8000/health',
                        'predict' => 'http://fastapi:8000/predict',
                        'batch_predict' => 'http://fastapi:8000/batch_predict'
                    ],
                    'input_features' => [
                        'total_price', 'total_items', 'total_payment', 'payment_count',
                        'distance', 'delivery_time', 'product_category_name'
                    ]
                ]
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to get model information',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get training status
     */
    public function getTrainingStatus()
    {
        try {
            // Placeholder - you can implement actual training status tracking
            return response()->json([
                'status' => 'success',
                'message' => 'Training status retrieved',
                'data' => [
                    'training_status' => 'completed',
                    'last_trained' => '2024-01-01 00:00:00',
                    'model_accuracy' => 0.85,
                    'training_samples' => 1000,
                    'model_version' => '1.0.0'
                ]
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to get training status',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Prepare prediction data from order
     */
    private function preparePredictionData($order, $distance = null, $deliveryTime = null)
    {
        // Get product category from the first item in the order
        $productCategory = 'unknown';
        if (!empty($order->items)) {
            $firstItem = $order->items[0];
            $product = Product::find($firstItem['product_id']);
            if ($product) {
                $productCategory = $product->category ?? 'unknown';
            }
        }
        
        // Use provided distance and delivery time or fallback to 0
        $distance = $distance ?? 0;
        $deliveryTime = $deliveryTime ?? 0;
        
        // Ensure delivery_time is an integer as expected by the AI service
        $deliveryTime = (int) round($deliveryTime);
        
        return [
            'total_price' => (float) $order->total_amount,
            'total_items' => $order->total_items,
            'total_payment' => (float) $order->total_amount, // Assuming payment equals total for now
            'payment_count' => 1, // Assuming single payment for now
            'distance' => $distance,
            'delivery_time' => $deliveryTime,
            'product_category_name' => $productCategory
        ];
    }

    /**
     * Calculate distance (placeholder implementation)
     */
    private function calculateDistance(Order $order)
    {
        // Placeholder - implement actual distance calculation based on shipping address
        // For now, return a random distance between 1-50 km
        return rand(1, 50) + (rand(0, 99) / 100);
    }

    /**
     * Estimate delivery time (placeholder implementation)
     */
    private function estimateDeliveryTime(Order $order)
    {
        // Placeholder - implement actual delivery time estimation
       // For now, return a random time between 1-48 hours
        return rand(1, 48);
    }

    /**
     * Get order from request data (for manual testing)
     */
    private function getOrderFromRequest(Request $request)
    {
        // Create a mock order from request data for testing
        $orderData = $request->validate([
            'total_price' => 'required|numeric',
            'total_items' => 'required|integer',
            'total_payment' => 'required|numeric',
            'payment_count' => 'required|integer',
            'distance' => 'required|numeric',
            'delivery_time' => 'required|integer',
            'product_category_name' => 'required|string'
        ]);
        
        // Create a mock order object
        $order = new Order();
        $order->total_amount = $orderData['total_price'];
        $order->total_items = $orderData['total_items'];
        // Add other properties as needed
        
        return $order;
    }

    /**
     * Interpret prediction result
     */
    private function interpretPrediction($prediction)
    {
        // 0 = not satisfied, 1 = satisfied
        return $prediction == 1 ? 'Customer will be satisfied' : 'Customer may not be satisfied';
    }
}
