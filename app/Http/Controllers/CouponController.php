<?php

namespace App\Http\Controllers;

use App\Models\Coupon;
use App\Services\CouponService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Spatie\Permission\Traits\HasRoles;

class CouponController extends Controller
{
    protected $couponService;

    public function __construct(CouponService $couponService)
    {
        $this->couponService = $couponService;
    }

    /**
     * Validate and apply a coupon code.
     */
    public function validateCoupon(Request $request)
    {
        $request->validate([
            'code' => 'required|string|max:20',
            'order_total' => 'required|numeric|min:0'
        ]);

        $user = Auth::user();
        $result = $this->couponService->applyCoupon(
            $request->input('code'),
            $user,
            $request->input('order_total')
        );

        return response()->json($result);
    }

    /**
     * Get user's available coupons.
     */
    public function getUserCoupons()
    {
        $user = Auth::user();
        $coupons = $this->couponService->getUserCoupons($user);

        return response()->json([
            'success' => true,
            'coupons' => $coupons
        ]);
    }

    /**
     * Admin: Get all coupons with pagination.
     */
    public function index(Request $request)
    {
        // Only allow admin access
        if (!Auth::user() || !method_exists(Auth::user(), 'hasRole') || !Auth::user()->hasRole('admin')) {
            abort(403, 'Unauthorized access');
        }

        $perPage = $request->input('per_page', 15);
        $coupons = Coupon::with(['user', 'order'])
            ->orderBy('created_at', 'desc')
            ->paginate($perPage);

        return response()->json([
            'success' => true,
            'coupons' => $coupons
        ]);
    }

    /**
     * Admin: Create a coupon manually.
     */
    public function store(Request $request)
    {
        // Only allow admin access
        $user = Auth::user();
        if (!$user || !method_exists($user, 'hasRole') || !$user->hasRole('admin')) {
            abort(403, 'Unauthorized access');
        }

        $request->validate([
            'discount_amount' => 'nullable|numeric|min:0',
            'discount_percentage' => 'nullable|numeric|min:0|max:100',
            'min_order_amount' => 'required|numeric|min:0',
            'user_id' => 'nullable|exists:users,id',
            'expires_at' => 'nullable|date|after:now',
            'reason' => 'nullable|string|max:255'
        ]);

        // Ensure at least one discount type is provided
        if (!$request->has('discount_amount') && !$request->has('discount_percentage')) {
            return response()->json([
                'success' => false,
                'message' => 'Either discount_amount or discount_percentage must be provided'
            ], 422);
        }

        try {
            $coupon = Coupon::create([
                'code' => Coupon::generateCode(),
                'discount_amount' => $request->input('discount_amount'),
                'discount_percentage' => $request->input('discount_percentage'),
                'min_order_amount' => $request->input('min_order_amount'),
                'user_id' => $request->input('user_id'),
                'expires_at' => $request->input('expires_at'),
                'reason' => $request->input('reason', 'Manual creation by admin')
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Coupon created successfully',
                'coupon' => $coupon
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to create coupon: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Admin: Delete a coupon.
     */
    public function destroy(Coupon $coupon)
    {
        // Only allow admin access
        if (!Auth::user()->hasRole('admin')) {
            abort(403, 'Unauthorized access');
        }

        try {
            $coupon->delete();

            return response()->json([
                'success' => true,
                'message' => 'Coupon deleted successfully'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to delete coupon: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get coupon statistics for dashboard.
     */
    public function statistics()
    {
        // Only allow admin access
        if (!Auth::user()->hasRole('admin')) {
            abort(403, 'Unauthorized access');
        }

        $totalCoupons = Coupon::count();
        $usedCoupons = Coupon::where('is_used', true)->count();
        $activeCoupons = Coupon::where('is_used', false)
            ->where(function ($query) {
                $query->whereNull('expires_at')
                      ->orWhere('expires_at', '>', now());
            })
            ->count();

        $totalDiscountGiven = Coupon::where('is_used', true)
            ->join('orders', 'coupons.order_id', '=', 'orders.id')
            ->sum('orders.discount_amount');

        return response()->json([
            'success' => true,
            'statistics' => [
                'total_coupons' => $totalCoupons,
                'used_coupons' => $usedCoupons,
                'active_coupons' => $activeCoupons,
                'total_discount_given' => (float) $totalDiscountGiven
            ]
        ]);
    }
}
