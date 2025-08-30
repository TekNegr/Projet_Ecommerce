<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Order extends Model
{
    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'user_id',
        'seller_ids',
        'total_amount',
        'status',
        'shipping_address',
        'notes',
        'items',
        'items_shipped',
        'coupon_id',
        'discount_amount',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'total_amount' => 'decimal:2',
        'discount_amount' => 'decimal:2',
        'shipping_address' => 'array',
        'seller_ids' => 'array',
        'items' => 'array',
        'items_shipped' => 'array',
    ];

    /**
     * Get the customer that owns the order.
     */
    public function customer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    /**
     * Get the coupon applied to the order.
     */
    public function coupon(): BelongsTo
    {
        return $this->belongsTo(Coupon::class);
    }

    /**
     * Get the reviews for the order.
     */
    public function reviews()
    {
        return $this->hasMany(Review::class);
    }

    /**
     * Check if the current user has reviewed this order.
     */
    public function hasUserReviewed()
    {
        return $this->reviews()->where('user_id', auth()->id())->exists();
    }

    /**
     * Check if order is pending.
     */
    public function isPending(): bool
    {
        return $this->status === 'pending';
    }

    /**
     * Check if order is processing.
     */
    public function isProcessing(): bool
    {
        return $this->status === 'processing';
    }

    /**
     * Check if order is delivered.
     */
    public function isDelivered(): bool
    {
        return $this->status === 'delivered';
    }

    /**
     * Check if order is cancelled.
     */
    public function isCancelled(): bool
    {
        return $this->status === 'cancelled';
    }

    /**
     * Get the total number of items in the order.
     */
    public function getTotalItemsAttribute(): int
    {
        if (empty($this->items)) {
            return 0;
        }

        return array_reduce($this->items, function ($total, $item) {
            return $total + $item['quantity'];
        }, 0);
    }

    /**
     * Get product IDs from order items.
     */
    public function getProductIdsAttribute(): array
    {
        if (empty($this->items)) {
            return [];
        }

        return array_map(function ($item) {
            return $item['product_id'];
        }, $this->items);
    }

    /**
     * Notify all sellers about a new order
     */
    public function notifySellersNewOrder()
    {
        if (empty($this->seller_ids)) {
            return;
        }

        foreach ($this->seller_ids as $sellerId) {
            \App\Models\Notification::createForSellerNewOrder($sellerId, $this);
        }
    }

    /**
     * Notify customer about order status change
     */
    public function notifyCustomerStatusChange($newStatus)
    {
        \App\Models\Notification::createForOrderStatusChange($this->user_id, $this, $newStatus);
        
        // Notify customer for review request if the order is delivered
        if ($newStatus === 'delivered') {
            \App\Models\Notification::createForReviewRequest($this->user_id, $this);
        }
    }

    /**
     * Notify sellers about order cancellation by customer
     */
    public function notifySellersOrderCancellation()
    {
        if (empty($this->seller_ids)) {
            return;
        }

        foreach ($this->seller_ids as $sellerId) {
            \App\Models\Notification::createForSellerOrderCancellation($sellerId, $this);
        }
    }
}
