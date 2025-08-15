<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Session;

class OrderController extends Controller
{
    /**
     * Display user's orders.
     */
    public function index()
    {
        $orders = Order::where('user_id', Auth::id())
            ->with(['seller'])
            ->latest()
            ->paginate(10);
            
        return view('orders.index', compact('orders'));
    }

    /**
     * Display a specific order.
     */
    public function show(Order $order)
    {
        // Check if the user can view this order
        if ($order->user_id !== Auth::id()) {
            abort(403, 'Unauthorized access to this order.');
        }

        // Load product details for items
        $productIds = collect($order->items)->pluck('product_id');
        $products = Product::whereIn('id', $productIds)->get()->keyBy('id');
        
        return view('orders.show', compact('order', 'products'));
    }

    /**
     * Create order from cart.
     */
    public function store(Request $request)
    {
        $cart = Session::get('cart', []);
        
        if (empty($cart)) {
            return redirect()->route('cart.index')->with('error', 'Your cart is empty!');
        }
        
        $request->validate([
            'shipping_address' => 'required|array',
            'shipping_address.name' => 'required|string|max:255',
            'shipping_address.address' => 'required|string|max:255',
            'shipping_address.city' => 'required|string|max:255',
            'shipping_address.postal_code' => 'required|string|max:20',
            'notes' => 'nullable|string|max:1000',
        ]);
        
        // Get products and calculate totals
        $products = Product::whereIn('id', array_keys($cart))->get()->keyBy('id');
        $items = [];
        $totalAmount = 0;
        
        foreach ($cart as $productId => $quantity) {
            $product = $products[$productId];
            $subtotal = $product->price * $quantity;
            
            $items[] = [
                'product_id' => $productId,
                'product_name' => $product->name,
                'price' => $product->price,
                'quantity' => $quantity,
                'subtotal' => $subtotal
            ];
            
            $totalAmount += $subtotal;
        }
        
        // Group items by seller (assuming all products have same seller for now)
        $sellerId = $products->first()->user_id;
        
        // Create order
        $order = Order::create([
            'user_id' => Auth::id(),
            'seller_id' => $sellerId,
            'total_amount' => $totalAmount,
            'status' => 'pending',
            'shipping_address' => $request->shipping_address,
            'notes' => $request->notes,
            'items' => $items,
        ]);
        
        // Clear cart
        Session::forget('cart');
        
        return redirect()->route('orders.show', $order)->with('success', 'Order placed successfully!');
    }

    /**
     * Display order creation form.
     */
    public function create()
    {
        $cart = Session::get('cart', []);
        
        if (empty($cart)) {
            return redirect()->route('cart.index')->with('error', 'Your cart is empty!');
        }
        
        $products = Product::whereIn('id', array_keys($cart))->get();
        $cartItems = [];
        $total = 0;
        
        foreach ($products as $product) {
            $quantity = $cart[$product->id];
            $subtotal = $product->price * $quantity;
            $cartItems[] = [
                'product' => $product,
                'quantity' => $quantity,
                'subtotal' => $subtotal
            ];
            $total += $subtotal;
        }
        
        return view('orders.create', compact('cartItems', 'total'));
    }

    /**
     * Cancel an order.
     */
    public function cancel(Order $order)
    {
        // Check if the user can cancel this order
        if ($order->user_id !== Auth::id()) {
            abort(403, 'Unauthorized access to cancel this order.');
        }
        
        if ($order->status === 'pending') {
            $order->update(['status' => 'cancelled']);
            return redirect()->back()->with('success', 'Order cancelled successfully!');
        }
        
        return redirect()->back()->with('error', 'Cannot cancel this order!');
    }
}
