<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('My Orders') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white overflow-hidden shadow-xl sm:rounded-lg p-6">
                <div class="text-center">
                    <h3 class="text-lg font-semibold mb-4">Seller Orders Management</h3>
                    <p class="text-gray-600 mb-6">Manage and track all orders for your products</p>
                    
                    <a href="{{ route('seller.orders.index') }}" class="inline-flex items-center px-6 py-3 bg-indigo-600 border border-transparent rounded-md font-semibold text-white hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 transition text-lg">
                        View All Orders
                    </a>
                    
                    <div class="mt-8 grid grid-cols-1 md:grid-cols-3 gap-6">
                        <div class="bg-blue-50 p-4 rounded-lg">
                            <h4 class="font-semibold text-blue-800">Order Management</h4>
                            <p class="text-sm text-blue-600 mt-2">View and manage all orders placed for your products</p>
                        </div>
                        
                        <div class="bg-green-50 p-4 rounded-lg">
                            <h4 class="font-semibold text-green-800">Status Updates</h4>
                            <p class="text-sm text-green-600 mt-2">Update order status from pending to completed</p>
                        </div>
                        
                        <div class="bg-purple-50 p-4 rounded-lg">
                            <h4 class="font-semibold text-purple-800">Customer Details</h4>
                            <p class="text-sm text-purple-600 mt-2">Access customer information and shipping details</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
