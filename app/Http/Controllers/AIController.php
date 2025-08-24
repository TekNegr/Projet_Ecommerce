<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;

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
        
        // Get customers and products for the testing interface
        $customers = \App\Models\User::whereHas('roles', function($query) {
            $query->where('name', 'customer');
        })->get();
        
        $products = \App\Models\Product::where('status', 'active')->get();
        
        return view('admin.ai-dashboard', [
            'healthStatus' => $healthStatus,
            'customers' => $customers,
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

    // Placeholder methods for future AI testing functionality
    public function testPrediction()
    {
        // To be implemented when model is ready
        return response()->json([
            'status' => 'not_implemented',
            'message' => 'Prediction functionality not yet implemented'
        ]);
    }

    public function getModelInfo()
    {
        // To be implemented when model is ready
        return response()->json([
            'status' => 'not_implemented',
            'message' => 'Model information not yet available'
        ]);
    }

    public function getTrainingStatus()
    {
        // To be implemented when model is ready
        return response()->json([
            'status' => 'not_implemented',
            'message' => 'Training status not yet available'
        ]);
    }
}
