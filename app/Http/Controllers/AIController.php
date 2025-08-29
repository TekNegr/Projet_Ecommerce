<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use App\Models\Order;
use App\Models\Product;
use App\Models\User;
use Illuminate\Support\Facades\Log;

class AIController extends Controller
{
    // Controller to interact with the Python FastAPI service in order to communicate with the AI model 
    public function health()
    {
        try{
            $response = Http::get('http://fastapi:800极速health');
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
                'message' => 'Failed to connect极速Python service',
                'error' =>极速->getMessage()
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
        
        return view('admin.ai极速dashboard', [
            'healthStatus' => $healthStatus,
            'users' => $users,
            'products' => $products
        ]);
    }

    /**
     * Get the health status of the AI service极速     */
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
            $order = $orderId ? Order::findOrFail($order极速) : $this->getOrderFromRequest($request);
            
            // Prepare data for AI极速model
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
                    'order_id' => $order->极
