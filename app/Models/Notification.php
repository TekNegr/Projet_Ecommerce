<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Notification extends Model
{
    protected $fillable = [
        'user_id',
        'order_id',
        'message',
        'type',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function order()
    {
        return $this->belongsTo(Order::class);
    }

    /**
     * Create a notification for customer order placement
     */
    public static function createForCustomerOrderPlaced($order)
    {
        return self::create([
            'user_id' => $order->user_id,
            'order_id' => $order->id,
            'message' => 'Your order #' . $order->id . ' has been placed successfully!',
            'type' => 'customer',
        ]);
    }

    /**
     * Create a notification for seller new order
     */
    public static function createForSellerNewOrder($sellerId, $order)
    {
        return self::create([
            'user_id' => $sellerId,
            'order_id' => $order->id,
            'message' => 'You have a new order #' . $order->id . ' with items from your store!',
            'type' => 'seller',
        ]);
    }

    /**
     * Create a notification for order status change
     */
    public static function createForOrderStatusChange($userId, $order, $newStatus)
    {
        $statusMessages = [
            'processing' => 'Your order #' . $order->id . ' is now being processed.',
            'shipped' => 'Your order #' . $order->id . ' has been shipped!',
            'delivered' => 'Your order #' . $order->id . ' has been delivered successfully!',
            'cancelled' => 'Your order #' . $order->id . ' has been cancelled.',
        ];

        return self::create([
            'user_id' => $userId,
            'order_id' => $order->id,
            'message' => $statusMessages[$newStatus] ?? 'Order #' . $order->id . ' status has been updated.',
            'type' => 'customer',
        ]);
    }

    /**
     * Create a notification for seller order cancellation
     */
    public static function createForSellerOrderCancellation($sellerId, $order)
    {
        return self::create([
            'user_id' => $sellerId,
            'order_id' => $order->id,
            'message' => 'Your items from order #' . $order->id . ' have been cancelled by the customer.',
            'type' => 'seller',
        ]);
    }

    /**
     * Create a notification for review request
     */
    public static function createForReviewRequest($userId, $order)
    {
        return self::create([
            'user_id' => $userId,
            'order_id' => $order->id,
            'message' => 'Your order #' . $order->id . ' has been delivered! Please leave a review to help us improve.',
            'type' => 'customer',
        ]);
    }

    /**
     * Create a notification for seller when a review is posted
     */
    public static function createForSellerReviewPosted($sellerId, $order, $review)
    {
        return self::create([
            'user_id' => $sellerId,
            'order_id' => $order->id,
            'message' => 'A customer has posted a review for order #' . $order->id . '. You can now answer the review.',
            'type' => 'seller_review',
        ]);
    }

    /**
     * Create a notification for customer when a seller answers their review
     */
    public static function createForCustomerReviewAnswered($customerId, $order, $review)
    {
        return self::create([
            'user_id' => $customerId,
            'order_id' => $order->id,
            'message' => 'A seller has responded to your review for order #' . $order->id . 'Saying :"' . $review->answer . '".',
            'type' => 'customer_review_answer',
        ]);
    }
}
