<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

class Coupon extends Model
{
    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'code',
        'discount_amount',
        'discount_percentage',
        'min_order_amount',
        'user_id',
        'order_id',
        'is_used',
        'used_at',
        'expires_at',
        'reason',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'discount_amount' => 'decimal:2',
        'discount_percentage' => 'decimal:2',
        'min_order_amount' => 'decimal:2',
        'is_used' => 'boolean',
        'used_at' => 'datetime',
        'expires_at' => 'datetime',
    ];

    /**
     * Get the user that owns the coupon.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the order that used the coupon.
     */
    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    /**
     * Generate a unique coupon code.
     */
    public static function generateCode(int $length = 8): string
    {
        do {
            $code = Str::upper(Str::random($length));
        } while (self::where('code', $code)->exists());

        return $code;
    }

    /**
     * Check if coupon is expired.
     */
    public function isExpired(): bool
    {
        return $this->expires_at && $this->expires_at->isPast();
    }

    /**
     * Check if coupon is valid for use.
     */
    public function isValid(): bool
    {
        return !$this->is_used && !$this->isExpired();
    }

    /**
     * Calculate discount amount for a given order total.
     */
    public function calculateDiscount(float $orderTotal): float
    {
        if (!$this->isValid() || $orderTotal < $this->min_order_amount) {
            return 0;
        }

        if ($this->discount_amount) {
            return min($this->discount_amount, $orderTotal);
        }

        if ($this->discount_percentage) {
            return $orderTotal * ($this->discount_percentage / 100);
        }

        return 0;
    }

    /**
     * Mark coupon as used.
     */
    public function markAsUsed(Order $order): bool
    {
        $this->is_used = true;
        $this->used_at = now();
        $this->order_id = $order->id;
        
        return $this->save();
    }
}
