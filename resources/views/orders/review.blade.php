<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Review Order #') }}{{ $order->id }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white overflow-hidden shadow-xl sm:rounded-lg">
                <div class="p-6">
                    @if (session('success'))
                        <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded relative mb-6" role="alert">
                            <span class="block sm:inline">{{ session('success') }}</span>
                        </div>
                    @endif

                    @if (session('error'))
                        <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded relative mb-6" role="alert">
                            <span class="block sm:inline">{{ session('error') }}</span>
                        </div>
                    @endif

                    <div class="mb-6">
                        <h3 class="text-lg font-medium text-gray-900">Order Details</h3>
                        <p class="text-sm text-gray-600">Order placed on: {{ $order->created_at->format('M d, Y') }}</p>
                        <p class="text-sm text-gray-600">Total: ${{ number_format($order->total_amount, 2) }}</p>
                    </div>

                    <form method="POST" action="{{ route('reviews.store', $order) }}">
                        @csrf

                        <!-- Rating -->
                        <div class="mb-6">
                            <label for="rating" class="block text-sm font-medium text-gray-700">Rating</label>
                            <div class="mt-1 flex items-center">
                                @for ($i = 1; $i <= 5; $i++)
                                    <input type="radio" id="rating-{{ $i }}" name="rating" value="{{ $i }}" 
                                           class="sr-only" {{ old('rating') == $i ? 'checked' : '' }}>
                                    <label for="rating-{{ $i }}" class="cursor-pointer">
                                        <svg class="h-8 w-8 {{ $i <= (old('rating') ?? 0) ? 'text-yellow-400' : 'text-gray-300' }}" 
                                             fill="currentColor" viewBox="0 0 20 20">
                                            <path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z" />
                                        </svg>
                                    </label>
                                @endfor
                            </div>
                            @error('rating')
                                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                            @enderror
                        </div>

                        <!-- Title -->
                        <div class="mb-6">
                            <label for="title" class="block text-sm font-medium text-gray-700">Review Title</label>
                            <input type="text" name="title" id="title" 
                                   value="{{ old('title') }}"
                                   class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm"
                                   placeholder="Enter a title for your review">
                            @error('title')
                                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                            @enderror
                        </div>

                        <!-- Comment -->
                        <div class="mb-6">
                            <label for="comment" class="block text-sm font-medium text-gray-700">Your Review</label>
                            <textarea name="comment" id="comment" rows="4"
                                      class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm"
                                      placeholder="Share your experience with this order">{{ old('comment') }}</textarea>
                            @error('comment')
                                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                            @enderror
                        </div>

                        <div class="flex items-center justify-end">
                            <a href="{{ route('orders.show', $order) }}" 
                               class="bg-gray-300 hover:bg-gray-400 text-gray-800 font-bold py-2 px-4 rounded mr-4">
                                Back to Order
                            </a>
                            <button type="submit" 
                                    class="bg-indigo-600 hover:bg-indigo-700 text-white font-bold py-2 px-4 rounded">
                                Submit Review
                            </button>
                        </div>
                    </form>

                    <!-- JavaScript for star rating interaction -->
                    <script>
                        document.addEventListener('DOMContentLoaded', function() {
                            const stars = document.querySelectorAll('input[name="rating"]');
                            const starLabels = document.querySelectorAll('label[for^="rating-"]');
                            
                            stars.forEach((star, index) => {
                                star.addEventListener('change', function() {
                                    // Update star colors based on selection
                                    starLabels.forEach((label, labelIndex) => {
                                        const svg = label.querySelector('svg');
                                        if (labelIndex <= index) {
                                            svg.classList.remove('text-gray-300');
                                            svg.classList.add('text-yellow-400');
                                        } else {
                                            svg.classList.remove('text-yellow-400');
                                            svg.classList.add('text-gray-300');
                                        }
                                    });
                                });
                            });
                        });
                    </script>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
