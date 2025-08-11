<?php

namespace App\Http\Controllers;

use App\Models\Product;
use Illuminate\Http\Request;

class ProductController extends Controller
{
    /**
     * Display a listing of all available products.
     */
    public function index()
    {
        $products = Product::with('seller')
            ->where('status', 'active')
            ->where('stock_quantity', '>', 0)
            ->latest()
            ->paginate(12);

        return view('products.index', compact('products'));
    }

    /**
     * Display the specified product.
     */
    public function show(Product $product)
    {
        if ($product->status !== 'active' || $product->stock_quantity <= 0) {
            abort(404);
        }

        return view('products.show', compact('product'));
    }
}
