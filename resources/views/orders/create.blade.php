<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            Checkout
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white overflow-hidden shadow-xl sm:rounded-lg">
                <div class="p-6">
                    <h3 class="text-lg font-medium text-gray-900 mb-4">Order Summary</h3>
                    
                    <div class="overflow-x-auto mb-6">
                        <table class="min-w-full divide-y divide-gray-200">
                            <thead>
                                <tr>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Product</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Price</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Quantity</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Subtotal</th>
                                </tr>
                            </thead>
                            <tbody class="bg-white divide-y divide-gray-200">
                                @foreach($cartItems as $item)
                                    <tr>
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            <div class="text-sm font-medium text-gray-900">
                                                {{ $item['product']->name }}
                                            </div>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                            ${{ number_format($item['product']->price, 2) }}
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                            {{ $item['quantity'] }}
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                            ${{ number_format($item['subtotal'], 2) }}
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>

                    <!-- Order Totals -->
                    <div class="text-right mb-4">
                        <p class="text-lg" id="subtotal-display">Subtotal: ${{ number_format($total, 2) }}</p>
                    </div>

                    <!-- Freight Cost -->
                    <div class="text-right mb-4">
                        <p class="text-lg" id="freight-display">Freight Cost: ${{ number_format($freightCost ?? 0, 2) }}</p>
                    </div>

                    <div class="text-right mb-6">
                        <p class="text-lg font-semibold" id="total-display">Total: ${{ number_format($total + ($freightCost ?? 0), 2) }}</p>
                    </div>

                    <!-- Coupon Input Section -->
                    <div class="mb-6 p-4 bg-gray-50 rounded-md">
                        <h4 class="text-md font-medium text-gray-900 mb-2">Apply Coupon</h4>
                        <div class="flex items-center space-x-2">
                            <input type="text" name="coupon_code" id="coupon_code" 
                                   placeholder="{{ $aiGeneratedCoupon ? 'AI Suggested Coupon: ' . $aiGeneratedCoupon->code : 'Enter coupon code' }}" 
                                   class="flex-1 border-gray-300 rounded-md shadow-sm" value="{{ old('coupon_code') }}">
                            <button type="button" id="applyCouponBtn" class="bg-blue-500 text-white px-4 py-2 rounded hover:bg-blue-600">
                                Apply
                            </button>
                        </div>
                        <div id="couponMessage" class="mt-2 text-sm"></div>
                        @error('coupon_code')
                            <p class="text-red-600 text-sm mt-1">{{ $message }}</p>
                        @enderror
                    </div>

                    <!-- Coupon Section -->
                    @if($coupon)
                        <div class="mb-6 p-4 bg-green-50 rounded-md">
                            <h4 class="text-md font-medium text-green-800 mb-2">Coupon Applied</h4>
                            <p class="text-green-700">
                                Code: {{ $coupon->code }}<br>
                                Discount: 
                                @if($coupon->discount_amount)
                                    ${{ number_format($coupon->discount_amount, 2) }} reduction
                                @elseif($coupon->discount_percentage)
                                    {{ $coupon->discount_percentage }}% reduction
                                @endif
                            </p>
                            <p class="text-green-700">
                                Minimum Order Amount: ${{ number_format($coupon->min_order_amount, 2) }}
                            </p>
                            @php
                                $discountAmount = 0;
                                if ($coupon->discount_amount) {
                                    $discountAmount = $coupon->discount_amount;
                                } elseif ($coupon->discount_percentage) {
                                    $discountAmount = ($total * $coupon->discount_percentage) / 100;
                                }
                                $newTotal = $total - $discountAmount;
                            @endphp
                            <p class="text-green-700 font-semibold">
                                Total Reduction: ${{ number_format($discountAmount, 2) }}
                            </p>
                            <p class="text-green-800 font-bold text-lg">
                                New Total: ${{ number_format($newTotal, 2) }}
                            </p>
                        </div>
                    @endif

                    <form action="{{ route('orders.store') }}" method="POST" id="orderForm">
                        @csrf
                        
                        <div class="mb-4">
                            <h4 class="text-md font-medium text-gray-900 mb-2">Shipping Address</h4>
                            
                            <div class="mb-4">
                                <label class="block text-sm font-medium text-gray-700 mb-2">Address Selection</label>
                                <div class="flex space-x-4">
                                    <label class="inline-flex items-center">
                                        <input type="radio" name="address_option" value="user_address" checked 
                                               class="address-option" onchange="toggleAddressFields()">
                                        <span class="ml-2">Use my saved address</span>
                                    </label>
                                    <label class="inline-flex items-center">
                                        <input type="radio" name="address_option" value="new_address" 
                                               class="address-option" onchange="toggleAddressFields()">
                                        <span class="ml-2">Enter new address</span>
                                    </label>
                                </div>
                            </div>

                            <div id="userAddressSection">
                                <div class="bg-gray-50 p-4 rounded-md mb-4">
                                    <p class="text-sm text-gray-600">
                                        {{ Auth::user()->zip_code }}, {{ Auth::user()->city }}, {{ Auth::user()->state }}, {{ Auth::user()->country }}
                                    </p>
                                </div>
                                <!-- Hidden fields for user's address data -->
                                <input type="hidden" name="shipping_address[name]" value="{{ Auth::user()->name }}">
                                <input type="hidden" name="shipping_address[address]" value="{{ Auth::user()->zip_code }}, {{ Auth::user()->city }}, {{ Auth::user()->state }}, {{ Auth::user()->country }}">
                                <input type="hidden" name="shipping_address[city]" value="{{ Auth::user()->city }}">
                                <input type="hidden" name="shipping_address[postal_code]" value="{{ Auth::user()->zip_code }}">
                                <input type="hidden" name="shipping_address[state]" value="{{ Auth::user()->state }}">
                                <input type="hidden" name="shipping_address[country]" value="{{ Auth::user()->country }}">
                            </div>

                            <div id="newAddressSection" style="display: none;">
                                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                    <div>
                                        <label for="name" class="block text-sm font-medium text-gray-700">Full Name</label>
                                        <input type="text" name="shipping_address[name]" id="name"
                                               class="mt-1 block w-full border-gray-300 rounded-md shadow-sm">
                                    </div>
                                    
                                    <div>
                                        <label for="address" class="block text-sm font-medium text-gray-700">Street Address</label>
                                        <input type="text" name="shipping_address[address]" id="address"
                                               class="mt-1 block w-full border-gray-300 rounded-md shadow-sm">
                                    </div>
                                    
                                    <div>
                                        <label for="city" class="block text-sm font-medium text-gray-700">City</label>
                                        <input type="text" name="shipping_address[city]" id="city"
                                               class="mt-1 block w-full border-gray-300 rounded-md shadow-sm">
                                    </div>
                                    
                                    <div>
                                        <label for="postal_code" class="block text-sm font-medium text-gray-700">Postal Code</label>
                                        <input type="text" name="shipping_address[postal_code]" id="postal_code"
                                               class="mt-1 block w-full border-gray-300 rounded-md shadow-sm">
                                    </div>
                                    
                                    <div>
                                        <label for="state" class="block text-sm font-medium text-gray-700">State/Province</label>
                                        <input type="text" name="shipping_address[state]" id="state"
                                               class="mt-1 block w-full border-gray-300 rounded-md shadow-sm">
                                    </div>
                                    
                                    <div>
                                        <label for="country" class="block text-sm font-medium text-gray-700">Country</label>
                                        <input type="text" name="shipping_address[country]" id="country"
                                               class="mt-1 block w-full border-gray-300 rounded-md shadow-sm">
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="mb-4">
                            <label for="notes" class="block text-sm font-medium text-gray-700">Order Notes (Optional)</label>
                            <textarea name="notes" id="notes" rows="3"
                                      class="mt-1 block w-full border-gray-300 rounded-md shadow-sm"></textarea>
                        </div>

                        <!-- Hidden field to store applied coupon code for form submission -->
                        <input type="hidden" name="coupon_code" id="coupon_code_hidden" value="">
                        
                        <div class="flex justify-between">
                            <a href="{{ route('cart.index') }}" class="bg-gray-500 text-white px-4 py-2 rounded hover:bg-gray-600">
                                Back to Cart
                            </a>
                            <button type="submit" class="bg-green-500 text-white px-6 py-2 rounded hover:bg-green-600">
                                Place Order
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <script>
        function toggleAddressFields() {
            const userAddressOption = document.querySelector('input[name="address_option"][value="user_address"]');
            const userAddressSection = document.getElementById('userAddressSection');
            const newAddressSection = document.getElementById('newAddressSection');
            
            if (userAddressOption.checked) {
                userAddressSection.style.display = 'block';
                newAddressSection.style.display = 'none';
                
                // Enable hidden fields for user address
                document.querySelectorAll('#userAddressSection input[type="hidden"]').forEach(input => {
                    input.disabled = false;
                });
                
                // Disable and clear new address fields
                document.querySelectorAll('#newAddressSection input').forEach(input => {
                    input.disabled = true;
                    input.removeAttribute('required');
                    input.value = '';
                });
            } else {
                userAddressSection.style.display = 'none';
                newAddressSection.style.display = 'block';
                
                // Disable hidden fields for user address
                document.querySelectorAll('#userAddressSection input[type="hidden"]').forEach(input => {
                    input.disabled = true;
                });
                
                // Enable and require new address fields
                document.querySelectorAll('#newAddressSection input').forEach(input => {
                    input.disabled = false;
                    input.setAttribute('required', 'required');
                });
            }
        }
        
        // Coupon application functionality
        function applyCoupon() {
            const couponCode = document.getElementById('coupon_code').value.trim();
            const messageDiv = document.getElementById('couponMessage');
            
            if (!couponCode) {
                messageDiv.innerHTML = '<span class="text-red-600">Please enter a coupon code</span>';
                return;
            }
            
            // Show loading state
            messageDiv.innerHTML = '<span class="text-blue-600">Applying coupon...</span>';
            
            // Send AJAX request to apply coupon
            fetch('{{ route("coupons.validate") }}', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': '{{ csrf_token() }}',
                    'Accept': 'application/json'
                },
                body: JSON.stringify({
                    code: couponCode,
                    order_total: {{ $total }} + {{ $freightCost ?? 0 }}
                })
            })
            .then(response => {
                console.log('Response status:', response.status);
                return response.json();
            })
            .then(data => {
                console.log('Response data:', data);
                if (data.success) {
                    messageDiv.innerHTML = '<span class="text-green-600">' + data.message + '</span>';
                    
                    // Update the total display with discount
                    const discountAmount = data.discount_amount || 0;
                    const newTotal = {{ $total }} + {{ $freightCost ?? 0 }} - discountAmount;
                    
                    // Update all total displays
                    const subtotalDisplay = document.getElementById('subtotal-display');
                    const freightDisplay = document.getElementById('freight-display');
                    const totalDisplay = document.getElementById('total-display');
                    
                    if (subtotalDisplay) {
                        subtotalDisplay.textContent = 'Subtotal: $' + parseFloat({{ $total }}).toFixed(2);
                    }
                    
                    if (freightDisplay) {
                        freightDisplay.textContent = 'Freight Cost: $' + parseFloat({{ $freightCost ?? 0 }}).toFixed(2);
                    }
                    
                    if (totalDisplay) {
                        totalDisplay.textContent = 'Total: $' + newTotal.toFixed(2);
                    }
                    
                    // Set the hidden coupon code field for form submission
                    const couponCodeHidden = document.getElementById('coupon_code_hidden');
                    if (couponCodeHidden) {
                        couponCodeHidden.value = couponCode;
                    }
                    
                    // Show discount information
                    const discountInfo = `
                        <div class="mb-6 p-4 bg-green-50 rounded-md">
                            <h4 class="text-md font-medium text-green-800 mb-2">Coupon Applied</h4>
                            <p class="text-green-700">
                                Code: ${data.coupon.code}<br>
                                Discount: $${parseFloat(data.discount_amount).toFixed(2)} reduction
                            </p>
                            <p class="text-green-700">
                                Minimum Order Amount: $${parseFloat(data.coupon.min_order_amount).toFixed(2)}
                            </p>
                            <p class="text-green-700 font-semibold">
                                Total Reduction: $${parseFloat(data.discount_amount).toFixed(2)}
                            </p>
                            <p class="text-green-800 font-bold text-lg">
                                New Total: $${newTotal.toFixed(2)}
                            </p>
                        </div>
                    `;
                    
                    // Remove existing coupon info if any
                    const existingCouponInfo = document.querySelector('.bg-green-50.rounded-md');
                    if (existingCouponInfo) {
                        existingCouponInfo.remove();
                    }
                    
                    // Insert new coupon info before the form
                    const form = document.getElementById('orderForm');
                    form.insertAdjacentHTML('beforebegin', discountInfo);
                    
                } else {
                    messageDiv.innerHTML = '<span class="text-red-600">' + data.message + '</span>';
                }
            })
            .catch(error => {
                console.error('Error:', error);
                messageDiv.innerHTML = '<span class="text-red-600">An error occurred while applying the coupon</span>';
            });
        }
        
        // Initialize on page load
        document.addEventListener('DOMContentLoaded', function() {
            toggleAddressFields();
            
            // Add coupon application event listener
            const applyCouponBtn = document.getElementById('applyCouponBtn');
            if (applyCouponBtn) {
                applyCouponBtn.addEventListener('click', applyCoupon);
            }
            
            // Add form submission debugging
            const orderForm = document.getElementById('orderForm');
            if (orderForm) {
                orderForm.addEventListener('submit', function(e) {
                    console.log('Form submitted');
                    console.log('Address option:', document.querySelector('input[name="address_option"]:checked').value);
                    
                    // Get coupon code from input
                    const couponCode = document.getElementById('coupon_code').value.trim();
                    console.log('Coupon code from input:', couponCode);
                    
                    // Log all form data
                    const formData = new FormData(this);
                    for (let [key, value] of formData.entries()) {
                        console.log(key + ': ' + value);
                    }
                    
                    // Check if form is valid
                    if (!this.checkValidity()) {
                        console.log('Form validation failed');
                        e.preventDefault();
                        this.reportValidity();
                    } else {
                        console.log('Form validation passed');
                    }
                });
            }
        });
    </script>
    
</x-app-layout>
