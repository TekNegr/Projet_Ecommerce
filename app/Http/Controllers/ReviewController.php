<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Models\Review;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;

class ReviewController extends Controller
{
    public function index()
    {
        // Code to list reviews
    }

    public function show(Order $order)
    {
        // Check if the user can review this order
        if ($order->user_id !== Auth::id()) {
            abort(403, 'Unauthorized access to review this order.');
        }

        // Check if the order is delivered
        if (!$order->isDelivered()) {
            abort(403, 'You can only review delivered orders.');
        }

        // Check if user already reviewed this order
        $existingReview = Review::where('user_id', Auth::id())
            ->where('order_id', $order->id)
            ->first();

        if ($existingReview) {
            return redirect()->route('orders.show', $order)
                ->with('flash.banner', 'You have already reviewed this order.')
                ->with('flash.bannerStyle', 'info');
        }

        return view('orders.review', compact('order'));
    }

    public function store(Request $request, Order $order)
    {
        // Check if the user can review this order
        if ($order->user_id !== Auth::id()) {
            abort(403, 'Unauthorized access to review this order.');
        }

        // Check if the order is delivered
        if (!$order->isDelivered()) {
            abort(403, 'You can only review delivered orders.');
        }

        // Check if user already reviewed this order
        $existingReview = Review::where('user_id', Auth::id())
            ->where('order_id', $order->id)
            ->first();

        if ($existingReview) {
            return redirect()->route('orders.show', $order)
                ->with('flash.banner', 'You have already reviewed this order.')
                ->with('flash.bannerStyle', 'info');
        }

        // Validate the request
        $validated = $request->validate([
            'rating' => 'required|integer|min:1|max:5',
            'title' => 'required|string|max:255',
            'comment' => 'required|string|max:1000',
        ]);

        // Create the review
        $review = Review::create([
            'user_id' => Auth::id(),
            'order_id' => $order->id,
            'rating' => $validated['rating'],
            'title' => $validated['title'],
            'comment' => $validated['comment'],
        ]);

        // Notify all sellers associated with this order about the new review
        if (!empty($order->seller_ids)) {
            foreach ($order->seller_ids as $sellerId) {
                \App\Models\Notification::createForSellerReviewPosted($sellerId, $order, $review);
            }
        }

        return redirect()->route('orders.show', $order)
            ->with('flash.banner', 'Thank you for your review!')
            ->with('flash.bannerStyle', 'success');
    }

    public function destroy($id)
    {
        // Code to delete a review
    }

    public function answer(Request $request, $id)
    {
        // Find the review
        $review = Review::findOrFail($id);
        $order = $review->order;

        // Check if the authenticated user is a seller associated with this order
        if (!auth()->user()->hasRole('seller') || 
            !in_array(auth()->id(), $order->seller_ids ?? [])) {
            abort(403, 'Unauthorized to answer this review.');
        }

        // Validate the answer
        $validated = $request->validate([
            'answer' => 'required|string|max:1000',
        ]);

        // Update the review with the seller's answer
        $review->update([
            'answer' => $validated['answer'],
        ]);

        // Notify the customer that their review has been answered
        \App\Models\Notification::createForCustomerReviewAnswered($review->user_id, $order, $review);

        return redirect()->back()
            ->with('flash.banner', 'Your answer has been submitted successfully!')
            ->with('flash.bannerStyle', 'success');
    }

    

    
}
