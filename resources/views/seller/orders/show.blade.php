<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Order Details') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white overflow-hidden shadow-xl sm:rounded-lg p-6">
                <div class="mb-6">
                    <a href="{{ route('seller.orders.index') }}" class="inline-flex items-center px-4 py-2 bg-gray-600 border border-transparent rounded-md font-semibold text-white hover:bg-gray-700 focus:outline-none focus:ring-2 focus:ring-gray-500 focus:ring-offset-2 transition">
                        ← Back to Orders
                    </a>
                </div>

                <!-- Order Summary -->
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-8">
                    <div class="bg-gray-50 p-4 rounded-lg">
                        <h3 class="text-lg font-semibold mb-3">Order Information</h3>
                        <div class="space-y-2">
                            <p><strong>Order ID:</strong> #{{ $order->id }}</p>
                            <p><strong>Order Date:</strong> {{ $order->created_at->format('F j, Y, g:i a') }}</p>
                            <p><strong>Status:</strong> {{ ucfirst($order->status) }}</p>
                            <div class="mt-4">
                                <form action="{{ route('seller.orders.continue', $order) }}" method="POST" class="inline">
                                    @csrf
                                    <button type="submit" class="bg-blue-500 text-white px-4 py-2 rounded hover:bg-blue-600">
                                        Continue
                                    </button>
                                </form>
                                <form action="{{ route('seller.orders.cancel', $order) }}" method="POST" class="inline">
                                    @csrf
                                    <button type="submit" class="bg-red-500 text-white px-4 py-2 rounded hover:bg-red-600" 
                                            onclick="return confirm('Are you sure you want to cancel your items from this order?')">
                                        Cancel
                                    </button>
                                </form>
                            </div>
                            <p><strong>Total Amount:</strong> ${{ number_format($order->total_amount, 2) }}</p>
                        </div>
                    </div>

                    <div class="bg-gray-50 p-4 rounded-lg">
                        <h3 class="text-lg font-semibold mb-3">Customer Information</h3>
                        <div class="space-y-2">
                            <p><strong>Name:</strong> {{ $order->customer->name }}</p>
                            <p><strong>Email:</strong> {{ $order->customer->email }}</p>
                            @if($order->customer->phone)
                                <p><strong>Phone:</strong> {{ $order->customer->phone }}</p>
                            @endif
                        </div>
                    </div>
                </div>

                <!-- Shipping Address -->
                <div class="mb-8">
                    <h3 class="text-lg font-semibold mb-3">Shipping Address</h3>
                    <div class="bg-gray-50 p-4 rounded-lg">
                        @if($order->shipping_address)
                            <p><strong>{{ $order->shipping_address['name'] ?? 'N/A' }}</strong></p>
                            <p>{{ $order->shipping_address['address'] ?? 'N/A' }}</p>
                            <p>
                                {{ $order->shipping_address['city'] ?? 'N/A' }}, 
                                {{ $order->shipping_address['state'] ?? 'N/A' }} 
                                {{ $order->shipping_address['postal_code'] ?? 'N/A' }}
                            </p>
                            <p>{{ $order->sh极ipping_address['country'] ?? 'N/A' }}</p>
                        @else
                            <p class="text-gray-500">No shipping address provided</p>
                        @endif
                    </div>
                </div>

                <!-- Order Items -->
                <div class="mb-8">
                    <h3 class="text-lg font-semibold mb-3">Order Items</h3>
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead>
                            <tr>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Product</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Price</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Quantity</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Subtotal</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Shipped</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200">
                            @if($order->items && count($order->items) > 0)
                                @foreach($order->items as $item)
                                    @php
                                        $product = \App\Models\Product::find($item['product_id']);
                                    @endphp
                                    <tr>
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            @if($product)
                                                {{ $product->name }}
                                            @else
                                                Product #{{ $item['product_id'] }} (may have been deleted)
                                            @endif
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap">${{ number_format($item['price'], 2) }}</td>
                                        <td class="px-6 py-4 whitespace-nowrap">{{ $item['quantity'] }}</td>
                                        <td class="px-6 py-4 whitespace-nowrap">${{ number_format($item['price'] * $item['quantity'], 2) }}</td>
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            @if(in_array($item['product_id'], $order->items_shipped ?? []))
                                                <span class="px-2 py-1 text-xs font-semibold rounded-full bg-green-100 text-green-800">Yes</span>
                                            @else
                                                <span class="px-2 py-1 text-xs font-semibold rounded-full bg-yellow-100 text-yellow-800">No</span>
                                            @endif
                                        </td>
                                    </tr>
                                @endforeach
                            @else
                                <tr>
                                    <td colspan="5" class="px-6 py-4 text-center text-gray-500">
                                        No items found in this order.
                                    </td>
                                </tr>
                            @endif
                        </tbody>
                        <tfoot>
                            <tr>
                                <td colspan="4" class="px-6 py-4 text-right font-semibold">Total:</td>
                                <td class="px-6 py-4 font-semibold">${{ number_format($order->total_amount, 2) }}</td>
                            </tr>
                        </tfoot>
                    </table>
                </div>

                <!-- Order Notes -->
                @if($order->notes)
                    <div class="mb-8">
                        <h3 class="text-lg font-semibold mb-3">Order Notes</h3>
                        <div class="bg-gray-50 p-4 rounded-lg">
                            <p>{{ $order->notes }}</p>
                        </div>
                    </div>
                @endif

                <!-- Reviews Section -->
                <div class="mb-8">
                    <h3 class="text-lg font-semibold mb-3">Customer Reviews</h3>
                    @php
                        $reviews = $order->reviews;
                    @endphp
                    
                    @if($reviews->count() > 0)
                        @foreach($reviews as $review)
                            <div class="bg-gray-50 p-4 rounded-lg mb-4">
                                <div class="flex items-center justify-between mb-2">
                                    <div class="flex items-center">
                                        <span class="text-yellow-400 text-lg mr-1">
                                            @for($i = 1; $i <= 5; $i++)
                                                @if($i <= $review->rating)
                                                    ★
                                                @else
                                                    ☆
                                                @endif
                                            @endfor
                                        </span>
                                        <span class="text-sm text-gray-600 ml-2">{{ $review->rating }}/5</span>
                                    </div>
                                    <span class="text-sm text-gray-500">{{ $review->created_at->format('M d, Y') }}</span>
                                </div>
                                
                                <h4 class="font-semibold text-gray-800 mb-2">{{ $review->title }}</h4>
                                <p class="text-gray-600 mb-3">{{ $review->comment }}</p>
                                
                                @if($review->isAnswered())
                                    <div class="bg-blue-50 p-3 rounded border-l-4 border-blue-400 mt-3">
                                        <p class="text-sm text-blue-800"><strong>Your Response:</strong> {{ $review->answer }}</p>
                                    </div>
                                @else
                                    <form action="{{ route('reviews.answer', $review) }}" method="POST" class="mt-3">
                                        @csrf
                                        <div class="mb-2">
                                            <label for="answer-{{ $review->id }}" class="block text-sm font-medium text-gray-700">Your Response:</label>
                                            <textarea 
                                                name="answer" 
                                                id="answer-{{ $review->id }}" 
                                                rows="3" 
                                                class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm"
                                                placeholder="Write your response to this review..."
                                                required
                                            ></textarea>
                                        </div>
                                        <button type="submit" class="bg-indigo-600 text-white px-4 py-2 rounded hover:bg-indigo-700">
                                            Submit Response
                                        </button>
                                    </form>
                                @endif
                            </div>
                        @endforeach
                    @else
                        <div class="bg-gray-50 p-4 rounded-lg text-center">
                            <p class="text-gray-500">No reviews have been submitted for this order yet.</p>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
