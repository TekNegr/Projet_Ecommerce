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
                
                <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                    <!-- Customer Selection -->
                    <div>
                        <label for="customer-select" class="block text-sm font-medium text-gray-700 mb-2">Select Customer</label>
                        <select id="customer-select" class="w-full border-gray-300 rounded-md shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                            <option value="">-- Select Customer --</option>
                            @foreach($customers as $customer)
                                <option value="{{ $customer->id }}">{{ $customer->name }} ({{ $customer->email }})</option>
                            @endforeach
                        </select>
                    </div>

                    <!-- Product Selection with Checkboxes -->
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Select Products</label>
                        <div class="border border-gray-300 rounded-md p-3 max-h-60 overflow-y-auto">
                            @foreach($products as $product)
                                <div class="flex items-center m-5 mb-2">
                                    <input type="checkbox" 
                                           id="product-{{ $product->id }}" 
                                           name="products[]" 
                                           value="{{ $product->id }}" 
                                           class="h-4 w-4 text-indigo-600 focus:ring-indigo-500 border-gray-300 rounded">
                                    <label for="product-{{ $product->id }}" class="ml-2 text-sm text-gray-700">
                                        {{ $product->name }} - ${{ $product->price }}
                                    </label>
                                </div>
                            @endforeach
                        </div>
                        <p class="text-sm text-gray-500 mt-1">Select products using checkboxes</p>
                    </div>
                    
                    <!-- Order Summary Section -->
                    <div>
                        <h4 class="text-md font-medium text-gray-700 mb-3">Order Summary</h4>
                        <div class="border border-gray-300 rounded-md p-4 bg-gray-50">
                            <div class="space-y-2">
                                <div class="flex justify-between">
                                    <span class="text-sm text-gray-600">Total Amount:</span>
                                    <span class="text-sm font-medium" id="total-amount">$0.00</span>
                                </div>
                                <div class="flex justify-between">
                                    <span class="text-sm text-gray-600">Delivery Distance:</span>
                                    <span class="text-sm font-medium" id="delivery-distance">-</span>
                                </div>
                                <div class="flex justify-between">
                                    <span class="text-sm text-gray-600">Predicted Delivery Time:</span>
                                    <span class="text-sm font-medium" id="predicted-delivery-time">-</span>
                                </div>
                                <div class="flex justify-between">
                                    <span class="text-sm text-gray-600">Satisfaction Score:</span>
                                    <span class="text-sm font-medium" id="satisfaction-score">-</span>
                                </div>
                            </div>
                            
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
