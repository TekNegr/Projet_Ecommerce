<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;

class AIController extends Controller
{

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
}
