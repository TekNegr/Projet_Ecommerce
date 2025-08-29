<?php

namespace App\Http\Controllers;

use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Session;

class CartController extends Controller
{
    /**
     * Display the shopping cart.
     */
    public function index()
    {
        $cart = Session::get('cart', []);
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
        
        return view('cart.index', compact('cartItems', 'total'));
    }

    /**
     * Add a product to the cart.
     */
    public function add(Request $request)
    {
        $request->validate([
            'product_id' => 'required|exists:products,id',
            'quantity' => 'required|integer|min:1'
        ]);
        
        $product = Product::find($request->product_id);
        $cart = Session::get('cart', []);
        $productId = $request->product_id;
        $quantity = $request->quantity;
        
        // Check if requested quantity exceeds available stock
        $currentCartQuantity = $cart[$productId] ?? 0;
        if ($currentCartQuantity + $quantity > $product->stock_quantity) {
            return redirect()->back()->with('flash.banner', 'Cannot add to cart: Insufficient stock available!')->with('flash.bannerStyle', 'danger');
        }
        
        if (isset($cart[$productId])) {
            $cart[$productId] += $quantity;
        } else {
            $cart[$productId] = $quantity;
        }
        
        Session::put('cart', $cart);
        
        return redirect()->back()->with('flash.banner', 'Product added to cart!')->with('flash.bannerStyle', 'success');
    }

    /**
     * Update the quantity of a product in the cart.
     */
    public function update(Request $request)
    {
        $request->validate([
            'product_id' => 'required|exists:products,id',
            'quantity' => 'required|integer|min:0'
        ]);
        
        $product = Product::find($request->product_id);
        $cart = Session::get('cart', []);
        $productId = $request->product_id;
        $quantity = $request->quantity;
        
        // Check if requested quantity exceeds available stock
        if ($quantity > 0 && $quantity > $product->stock_quantity) {
            return redirect()->back()->with('error', 'Cannot update cart: Requested quantity exceeds available stock!');
        }
        
        if ($quantity <= 0) {
            unset($cart[$productId]);
        } else {
            $cart[$productId] = $quantity;
        }
        
        Session::put('cart', $cart);
        
        return redirect()->back()->with('success', 'Cart updated!');
    }

    /**
     * Remove a product from the cart.
     */
    public function remove(Request $request)
    {
        $request->validate([
            'product_id' => 'required|exists:products,id'
        ]);
        
        $cart = Session::get('cart', []);
        $productId = $request->product_id;
        
        if (isset($cart[$productId])) {
            unset($cart[$productId]);
            Session::put('cart', $cart);
        }
        
        return redirect()->back()->with('flash.banner', 'Product removed from cart!')->with('flash.bannerStyle', 'success');
    }

    /**
     * Clear the entire cart.
     */
    public function clear()
    {
        Session::forget('cart');
        return redirect()->back()->with('flash.banner', 'Cart cleared!')->with('flash.bannerStyle', 'success');
    }

    /**
     * Get cart count for display in navigation.
     */
    public function count()
    {
        $cart = Session::get('cart', []);
        return array_sum($cart);
    }
}
