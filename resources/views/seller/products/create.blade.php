<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Add New Product') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-3xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white overflow-hidden shadow-xl sm:rounded-lg p-6">
                <form method="POST" action="{{ route('seller.products.store') }}" enctype="multipart/form-data">
                    @csrf

                    <div class="mb-4">
                        <x-label for="name" :value="__('Product Name')" />
                        <x-input id="name" class="block mt-1 w-full" type="text" name="name" :value="old('name')" required autofocus />
                        @error('name') <span class="text-red-600 text-sm">{{ $message }}</span> @enderror
                    </div>

                    <div class="mb-4">
                        <x-label for="description" :value="__('Description')" />
                        <textarea id="description" class="block mt-1 w-full border-gray-300 rounded-md" name="description">{{ old('description') }}</textarea>
                        @error('description') <span class="text-red-600 text-sm">{{ $message }}</span> @enderror
                    </div>

                    <div class="mb-4 grid grid-cols-2 gap-4">
                        <div>
                            <x-label for="price" :value="__('Price')" />
                            <x-input id="price" class="block mt-1 w-full" type="number" step="0.01" min="0" name="price" :value="old('price')" required />
                            @error('price') <span class="text-red-600 text-sm">{{ $message }}</span> @enderror
                        </div>

                        <div>
                            <x-label for="stock_quantity" :value="__('Stock Quantity')" />
                            <x-input id="stock_quantity" class="block mt-1 w-full" type="number" min="0" name="stock_quantity" :value="old('stock_quantity')" required />
                            @error('stock_quantity') <span class="text-red-600 text-sm">{{ $message }}</span> @enderror
                        </div>
                    </div>

                    <div class="mb-4">
                        <x-label for="category" :value="__('Category')" />
                        <x-input id="category" class="block mt-1 w-full" type="text" name="category" :value="old('category')" />
                        @error('category') <span class="text-red-600 text-sm">{{ $message }}</span> @enderror
                    </div>

                    <div class="mb-4">
                        <x-label for="images" :value="__('Product Images')" />
                        <input id="images" class="block mt-1 w-full" type="file" name="images[]" multiple accept="image/*" />
                        @error('images') <span class="text-red-600 text-sm">{{ $message }}</span> @enderror
                        @error('images.*') <span class="text-red-600 text-sm">{{ $message }}</span> @enderror

                        @if(old('images'))
                            <div class="mt-2 flex space-x-2 overflow-x-auto">
                                @foreach(old('images') as $image)
                                    <img src="{{ asset('storage/' . $image) }}" alt="Product Image" class="h-20 rounded-md object-cover" />
                                @endforeach
                            </div>
                        @endif
                    </div>

                    <div class="mb-4">
                        <x-label for="status" :value="__('Status')" />
                        <select id="status" name="status" class="block mt-1 w-full border-gray-300 rounded-md" required>
                            <option value="active" {{ old('status') == 'active' ? 'selected' : '' }}>Active</option>
                            <option value="inactive" {{ old('status') == 'inactive' ? 'selected' : '' }}>Inactive</option>
                        </select>
                        @error('status') <span class="text-red-600 text-sm">{{ $message }}</span> @enderror
                    </div>

                    <div class="flex items-center justify-end mt-6">
                        <x-button>
                            {{ __('Add Product') }}
                        </x-button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</x-app-layout>
