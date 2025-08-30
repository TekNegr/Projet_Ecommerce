<?php

namespace App\Services;

use App\Models\Coupon;
use App\Models\User;
use Illuminate\Support\Facades\Log;

class CouponService
{
    /**
     * Generate a coupon for a dissatisfied customer prediction.
     */
    public function generateDissatisfactionCoupon(User $user, float $orderAmount, string $reason = 'AI predicted dissatisfaction'): ?Coupon
    {
        try {
            // Calculate discount based on order amount (10-20% discount)
            $discountPercentage = rand(10, 20);
            $discountAmount = $orderAmount * ($discountPercentage / 100);
            
            // Set minimum order amount for next purchase
            $minOrderAmount = max($orderAmount * 0.5, 10); // At least 50% of original order or $10
            
            $coupon = Coupon::create([
                'code' => Coupon::generateCode(),
                'discount_percentage' => $discountPercentage,
                'discount_amount' => $discountAmount,
                'min_order_amount' => $minOrderAmount,
                'user_id' => $user->id,
                'expires_at' => now()->addDays(30), // Valid for 30 days
                'reason' => $reason,
            ]);
            
            Log::info('Generated dissatisfaction coupon', [
                'user_id' => $user->id,
                'coupon_id' => $coupon->id,
                'discount_percentage' => $discountPercentage,
                'discount_amount' => $discountAmount,
                'reason' => $reason
            ]);
            
            return $coupon;
            
        } catch (\Exception $e) {
            Log::error('Failed to generate coupon', [
                'user_id' => $user->id,
                'error' => $e->getMessage()
            ]);
            return null;
        }
    }

    /**
     * Validate and apply coupon to an order.
     */
    public function applyCoupon(string $code, User $user, float $orderTotal): array
    {
        $coupon = Coupon::where('code', $code)
            ->where(function ($query) use ($user) {
                $query->whereNull('user_id')
                      ->orWhere('user_id', $user->id);
            })
            ->first();

        if (!$coupon) {
            return [
                'success' => false,
                'message' => 'Coupon not found or invalid for this user'
            ];
        }

        if (!$coupon->isValid()) {
            return [
                'success' => false,
                'message' => 'Coupon is not valid (already used or expired)'
            ];
        }

        if ($orderTotal < $coupon->min_order_amount) {
            return [
                'success' => false,
                'message' => sprintf('Minimum order amount of $%.2f required', $coupon->min_order_amount)
            ];
        }

        $discount = $coupon->calculateDiscount($orderTotal);
        
        return [
            'success' => true,
            'coupon' => $coupon,
            'discount_amount' => $discount,
            'final_amount' => $orderTotal - $discount,
            'message' => 'Coupon applied successfully'
        ];
    }

    /**
     * Get user's valid coupons.
     */
    public function getUserCoupons(User $user): array
    {
        return Coupon::where('user_id', $user->id)
            ->where('is_used', false)
            ->where(function ($query) {
                $query->whereNull('expires_at')
                      ->orWhere('expires_at', '>', now());
            })
            ->get()
            ->toArray();
    }
}
