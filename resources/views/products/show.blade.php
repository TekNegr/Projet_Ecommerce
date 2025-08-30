<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ $product->name }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white overflow-hidden shadow-xl sm:rounded-lg">
                <div class="p-6">
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-8">
                        <!-- Product Image -->
                        <div>
                            <img src="{{ Storage::url($product->image_url ?? 'https://via.placeholder.com/400x300') }}" 
                                 alt="{{ $product->name }}" 
                                 class="w-full h-64 object-cover rounded-lg">
                        </div>

                        <!-- Product Details -->
                        <div>
                            <h1 class="text-3xl font-bold text-gray-900 mb-4">{{ $product->name }}</h1>
                            
                            <div class="mb-4">
                                <span class="text-2xl font-bold text-green-600">${{ number_format($product->price, 2) }}</span>
                            </div>

                            <div class="mb-4">
                                <h3 class="text-lg font-semibold text-gray-700 mb-2">Description</h3>
                                <p class="text-gray-600">{{ $product->description ?? 'No description available.' }}</p>
                            </div>

                            <div class="mb-4">
                                <h3 class="text-lg font-semibold text-gray-700 mb-2">Details</h3>
                                <ul class="text-gray-600 space-y-1">
                                    <li><strong>Category:</strong> {{ $product->category ?? 'N/A' }}</li>
                                    <li><strong>Stock:</strong> {{ $product->stock_quantity ?? 0 }} units available</li>
                                    <li><strong>Seller:</strong> {{ $product->seller->name ?? 'Unknown' }}</li>
                                </ul>
                            </div>

                            <!-- Add to Cart Form -->
                            <form action="{{ route('cart.add') }}" method="POST" class="mt-6">
                                @csrf
                                <input type="hidden" name="product_id" value="{{ $product->id }}">
                                
                                <div class="flex items-center space-x-4 mb-4">
                                    <label for="quantity" class="text-gray-700 font-medium">Quantity:</label>
                                    <input type="number" 
                                           name="quantity" 
                                           id="quantity" 
                                           value="1" 
                                           min="1" 
                                           max="{{ $product->stock_quantity }}"
                                           class="w-20 border-gray-300 rounded-md shadow-sm">
                                </div>

                                <button type="submit" 
                                        class="w-full bg-blue-600 text-white py-3 px-6 rounded-lg hover:bg-blue-700 transition duration-200">
                                    Add to Cart
                                </button>
                            </form>

                            <!-- Back to Products -->
                            <a href="{{ route('products.index') }}" 
                               class="mt-4 inline-block text-blue-600 hover:text-blue-800">
                                ← Back to Products
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
