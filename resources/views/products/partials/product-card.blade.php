{{-- Product Card Component --}}
@props(['product'])

<div class="bg-white rounded-lg shadow-md overflow-hidden hover:shadow-lg transition-shadow duration-300">
    <!-- Product Image -->
    <div class="aspect-w-16 aspect-h-9">
        @if($product->images)
            <img src="{{ asset('storage/' . $product->image) }}" 
                 alt="{{ $product->name }}" 
                 class="w-full h-48 object-cover">
        @else
            <div class="w-full h-48 bg-gray-200 flex items-center justify-center">
                <svg class="w-12 h-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" 
                          d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z">
                    </path>
                </svg>
            </div>
        @endif
    </div>

    <!-- Product Details -->
    <div class="p-4">
        <h3 class="text-lg font-semibold text-gray-900 mb-2 truncate">
            {{ $product->name }}
        </h3>
        
        <p class="text-gray-600 text-sm mb-3 line-clamp-2">
            {{ $product->description }}
        </p>

        <div class="flex items-center justify-between mb-3">
            <span class="text-2xl font-bold text-gray-900">
                ${{ number_format($product->price, 2) }}
            </span>
            
            @if($product->stock_quantity > 0)
                <span class="text-sm text-green-600 font-medium">
                    In Stock ({{ $product->stock_quantity }})
                </span>
            @else
                <span class="text-sm text-red-600 font-medium">
                    Out of Stock
                </span>
            @endif
        </div>

        <div class="flex items-center justify-between">
            <span class="text-sm text-gray-500">
                Seller: {{ $product->seller->name }}
            </span>
            
            <a href="{{ route('products.show', $product) }}" 
               class="inline-flex items-center px-3 py-2 bg-blue-600 text-white text-sm font-medium rounded-md hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
                View Details
            </a>
        </div>
    </div>
</div>
