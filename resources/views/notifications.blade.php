<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Notifications') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 bg-white border-b border-gray-200">
                    @if($notifications->isEmpty())
                        <p class="text-gray-600">You have no notifications.</p>
                    @else
                        <ul>
                            @foreach($notifications as $notification)
                                <li class="mb-4 p-4 border rounded-lg bg-gray-50">
                                    <p class="text-gray-800">{{ $notification->message }}</p>
                                    <p class="text-sm text-gray-500">Received on {{ $notification->created_at->format('M d, Y H:i') }}</p>
                                    <form action="{{ route('notifications.destroy', $notification) }}" method="POST" class="mt-2">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="text-red-600 hover:underline">Delete</button>
                                    </form>
                                </li>
                            @endforeach
                        </ul>
                    @endif
                </div>
            </div>
        </div>
    </div>
</x-app-layout>

