<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('AI Dashboard') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white overflow-hidden shadow-xl sm:rounded-lg p-6">
                <h3 class="text-lg font-medium">AI Model Status</h3>
                <p class="mt-2">{{ $healthStatus['message'] }}</p>
                <p class="mt-2">Python Status: <strong>{{ ucfirst($healthStatus['status']) ??  $healthStatus['error'] ?? 'Error. No additional details available.'}}</strong></p>

                <hr class="my-6">

                <h3 class="text-lg font-medium mt-5 mb-4">AI Model Testing Interface</h3>
                
                @if ($errors->any())
                    <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded mb-4">
                        <ul>
                            @foreach ($errors->all() as $error)
                                <li>{{ $error }}</li>
                            @endforeach
                        </ul>
                    </div>
                @endif

                @if (session('message'))
                    <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded mb-4">
                        {{ session('message') }}
                    </div>
                @endif

                <form method="POST" action="{{ route('ai.calculatePseudoOrder') }}">
                    @csrf
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                        <!-- User Selection -->
                        <div>
                            <label for="customer_id" class="block text-sm font-medium text-gray-700 mb-2">Select User</label>
                            <select id="customer_id" name="customer_id" class="w-full border-gray-300 rounded-md shadow-sm focus:border-indigo-500 focus:ring-indigo-500" required>
                                <option value="">-- Select User --</option>
                                @foreach($users as $user)
                                    <option value="{{ $user->id }}" {{ old('customer_id') == $user->id ? 'selected' : '' }}>
                                        {{ $user->name }} ({{ $user->email }})
                                    </option>
                                @endforeach
                            </select>
                        </div>

                        <!-- Product Selection with Checkboxes -->
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Select Products</label>
                            <div class="border border-gray-300 rounded-md p-3 max-h-60 overflow-y-auto">
                                @foreach($products as $product)
                                    <div class="flex items-center mb-2">
                                        <input type="checkbox" 
                                               id="product-{{ $product->id }}" 
                                               name="product_ids[]" 
                                               value="{{ $product->id }}" 
                                               class="h-4 w-4 text-indigo-600 focus:ring-indigo-500 border-gray-300 rounded"
                                               {{ in_array($product->id, old('product_ids', [])) ? 'checked' : '' }}>
                                        <label for="product-{{ $product->id }}" class="ml-2 text-sm text-gray-700">
                                            {{ $product->name }} - ${{ $product->price }}
                                        </label>
                                    </div>
                                @endforeach
                            </div>
                            <p class="text-sm text-gray-500 mt-1">Select products using checkboxes</p>
                        </div>
                        
                        <!-- Calculate Button -->
                        <div class="flex items-end">
                            <button type="submit" class="w-full bg-indigo-600 text-white py-2 px-4 rounded-md hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2">
                                Calculate Prediction
                            </button>
                        </div>
                    </div>
                </form>

                @if (isset($calculationResult))
                    <hr class="my-6">
                    <h3 class="text-lg font-medium mb-4">Calculation Results</h3>
                    
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <!-- Order Summary -->
                        <div>
                            <h4 class="text-md font-medium text-gray-700 mb-3">Order Summary</h4>
                            <div class="border border-gray-300 rounded-md p-4 bg-gray-50">
                                <div class="space-y-2">
                                    <div class="flex justify-between">
                                        <span class="text-sm text-gray-600">Customer:</span>
                                        <span class="text-sm font-medium">{{ $calculationResult['customer']->name }}</span>
                                    </div>
                                    <div class="flex justify-between">
                                        <span class="text-sm text-gray-600">Email:</span>
                                        <span class="text-sm font-medium">{{ $calculationResult['customer']->email }}</span>
                                    </div>
                                    <div class="flex justify-between">
                                        <span class="text-sm text-gray-600">Address:</span>
                                        <span class="text-sm font-medium">{{ $calculationResult['customer']->address() }}</span>
                                    </div>
                                    <div class="flex justify-between">
                                        <span class="text-sm text-gray-600">Subtotal:</span>
                                        <span class="text-sm font-medium">${{ number_format($calculationResult['totals']['subtotal'], 2) }}</span>
                                    </div>
                                    <div class="flex justify-between">
                                        <span class="text-sm text-gray-600">Freight Cost:</span>
                                        <span class="text-sm font-medium">${{ number_format($calculationResult['totals']['freight_cost'], 2) }}</span>
                                    </div>
                                    <div class="flex justify-between">
                                        <span class="text-sm text-gray-600">Total with Freight:</span>
                                        <span class="text-sm font-medium">${{ number_format($calculationResult['totals']['total_with_freight'], 2) }}</span>
                                    </div>
                                    <div class="flex justify-between">
                                        <span class="text-sm text-gray-600">Total Items:</span>
                                        <span class="text-sm font-medium">{{ $calculationResult['totals']['total_items'] }}</span>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Delivery & Prediction -->
                        <div>
                            <h4 class="text-md font-medium text-gray-700 mb-3">Delivery & Prediction</h4>
                            <div class="border border-gray-300 rounded-md p-4 bg-gray-50">
                                <div class="space-y-2">
                                    <div class="flex justify-between">
                                        <span class="text-sm text-gray-600">Delivery Distance:</span>
                                        <span class="text-sm font-medium">{{ $calculationResult['delivery']['total_distance_km'] }} km</span>
                                    </div>
                                    <div class="flex justify-between">
                                        <span class="text-sm text-gray-600">Predicted Delivery Time:</span>
                                        <span class="text-sm font-medium">{{ $calculationResult['delivery']['total_time_hours'] }} hours</span>
                                    </div>
                                    <div class="flex justify-between">
                                        <span class="text-sm text-gray-600">Number of Sellers:</span>
                                        <span class="text-sm font-medium">{{ $calculationResult['delivery']['sellers_count'] }}</span>
                                    </div>
                                    
                                    @if (isset($calculationResult['prediction']))
                                        <div class="mt-4 pt-4 border-t border-gray-200">
                                            <div class="flex justify-between">
                                                <span class="text-sm text-gray-600">Satisfaction Prediction:</span>
                                                <span class="text-sm font-medium">{{ $calculationResult['prediction']['interpretation'] }}</span>
                                            </div>
                                            <div class="flex justify-between">
                                                <span class="text-sm text-gray-600">Confidence Score:</span>
                                                <span class="text-sm font-medium">{{ number_format($calculationResult['prediction']['confidence'] * 100, 1) }}%</span>
                                            </div>
                                            <div class="flex justify-between">
                                                <span class="text-sm text-gray-600">Prediction Score:</span>
                                                <span class="text-sm font-medium">{{ $calculationResult['prediction']['score'] }}</span>
                                            </div>
                                        </div>
                                    @else
                                        <div class="mt-4 pt-4 border-t border-gray-200">
                                            <div class="text-sm text-yellow-600">
                                                AI prediction service is currently unavailable. Showing delivery calculations only.
                                            </div>
                                        </div>
                                    @endif
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Selected Products -->
                    <div class="mt-6">
                        <h4 class="text-md font-medium text-gray-700 mb-3">Selected Products</h4>
                        <div class="border border-gray-300 rounded-md p-4">
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                @foreach($calculationResult['products'] as $product)
                                    <div class="flex items-center justify-between p-3 bg-gray-50 rounded">
                                        <div>
                                            <span class="text-sm font-medium">{{ $product->name }}</span>
                                            <p class="text-sm text-gray-600">{{ $product->category }}</p>
                                        </div>
                                        <span class="text-sm font-medium">${{ number_format($product->price, 2) }}</span>
                                    </div>
                                @endforeach
                            </div>
                        </div>
                    </div>
                @endif
            </div>
        </div>
    </div>
</x-app-layout>
