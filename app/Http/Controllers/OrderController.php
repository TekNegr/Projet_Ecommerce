<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Models\Product;
use App\Services\AddressValidationService;
use App\Services\AICouponService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use App\Models\Notification;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Facades\Log;

class OrderController extends Controller
{
    /**
     * Display user's orders.
     */
    public function index()
    {
        $orders = Order::where('user_id', Auth::id())
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

        // Load order with items
        $order->load(['customer']);
        
        return view('orders.show', compact('order'));
    }

    /**
     * Create order from cart.
     */
    public function store(Request $request)
    {
        $cart = Session::get('cart', []);
        
        if (empty($cart)) {
            return redirect()->route('cart.index')->with('flash.banner', 'Your cart is empty!')->with('flash.bannerStyle', 'danger');
        }
        
        $request->validate([
            'address_option' => 'required|in:user_address,new_address',
            'shipping_address' => 'sometimes|array',
            'shipping_address.name' => 'required_if:address_option,new_address|string|max:255',
            'shipping_address.address' => 'required_if:address_option,new_address|string|max:255',
            'shipping_address.city' => 'required_if:address_option,new_address|string|max:255',
            'shipping_address.postal_code' => 'required_if:address_option,new_address|string|max:20',
            'shipping_address.state' => 'required_if:address_option,new_address|string|max:255',
            'shipping_address.country' => 'required_if:address_option,new_address|string|max:255',
            'notes' => 'nullable|string|max:1000',
            'coupon_code' => 'nullable|string|max:20',
        ]);
        
        // Prepare shipping address based on selection
        $shippingAddress = [];
        
        if ($request->address_option === 'user_address') {
            // Use user's saved address
            $user = Auth::user();
            $shippingAddress = [
                'name' => $user->name,
                'address' => $user->address(),
                'city' => $user->city,
                'postal_code' => $user->zip_code,
                'state' => $user->state,
                'country' => $user->country
            ];
        } else {
            // Use new address
            $shippingAddress = $request->shipping_address;
        }
        
        // Get products and calculate totals
        $products = Product::whereIn('id', array_keys($cart))->get()->keyBy('id');
        $totalAmount = 0;
        
        // Prepare items array
        $items = [];
        
        // Collect unique seller IDs
        $sellerIds = [];
        foreach ($cart as $productId => $quantity) {
            $product = $products[$productId];
            $subtotal = $product->price * $quantity;
            $totalAmount += $subtotal;

            $items[] = [
                'product_id' => $productId,
                'quantity' => $quantity,
                'price' => $product->price,
                'seller_id' => $product->user_id, // Store seller ID with item
            ];
            
            // Collect unique seller IDs
            if (!in_array($product->user_id, $sellerIds)) {
                $sellerIds[] = $product->user_id;
            }
        }

        // Calculate freight cost and delivery time using TravelEstimatorService
        $travelEstimator = new \App\Services\TravelEstimatorService();
        $mockOrder = new \stdClass();
        $mockOrder->customer = $user ?? Auth::user();
        $mockOrder->products = $products;
        $mockOrder->total_amount = $totalAmount;
        $mockOrder->total_items = array_sum(array_values($cart));
        $freightData = $travelEstimator->estimateTravel($mockOrder);

        $freightCost = $freightData['total_freight_cost'] ?? 0;
        $deliveryTimeHours = $freightData['total_time_hours'] ?? 0;

        // Add freight cost to total amount
        $totalAmount += $freightCost;

        // Initialize discount and coupon variables
        $discountAmount = 0;
        $appliedCoupon = null;

        // Validate and apply coupon if provided
        if ($request->filled('coupon_code')) {
            $couponService = new \App\Services\CouponService();
            $user = Auth::user();
            $couponResult = $couponService->applyCoupon($request->input('coupon_code'), $user, $totalAmount);

            if ($couponResult['success']) {
                $discountAmount = $couponResult['discount_amount'];
                $appliedCoupon = $couponResult['coupon'];
            } else {
                return redirect()->route('cart.index')->with('flash.banner', $couponResult['message'])->with('flash.bannerStyle', 'danger');
            }
        }
        
        // Use database transaction to ensure data consistency
        try {
            DB::beginTransaction();
            
            Log::info('Starting order creation process', ['cart' => $cart, 'products_count' => count($products)]);
            
            // Check stock availability and update stock quantity
            foreach ($cart as $productId => $quantity) {
                $product = $products[$productId];
                Log::info('Checking stock for product', ['product_id' => $productId, 'stock' => $product->stock_quantity, 'requested' => $quantity]);
                
                if ($product->stock_quantity < $quantity) {
                    Log::warning('Insufficient stock', ['product_id' => $productId, 'stock' => $product->stock_quantity, 'requested' => $quantity]);
                    DB::rollBack();
                    return redirect()->route('cart.index')->with('flash.banner', 'Insufficient stock for ' . $product->name)->with('flash.bannerStyle', 'danger');
                }
            }

            Log::info('Stock check passed, updating stock quantities');
            foreach ($cart as $productId => $quantity) {
                $product = $products[$productId];
                $product->stock_quantity -= $quantity; // Update stock quantity
                $product->save(); // Save changes to the product
                Log::info('Updated stock for product', ['product_id' => $productId, 'new_stock' => $product->stock_quantity]);
            }
            
            Log::info('Creating order with items', ['items_count' => count($items), 'total_amount' => $totalAmount]);
            // Create order
            $orderData = [
                'seller_ids' => $sellerIds, // Store seller IDs in the order
                'user_id' => Auth::id(),
                'total_amount' => $totalAmount - $discountAmount,
                'discount_amount' => $discountAmount,
                'status' => 'pending',
                'shipping_address' => $shippingAddress,
                'notes' => $request->notes,
                'items' => $items, // Store items directly (including seller IDs)
            ];

            if ($appliedCoupon) {
                $orderData['coupon_id'] = $appliedCoupon->id;
            }

            $order = Order::create($orderData);
            
            Log::info('Order created successfully', ['order_id' => $order->id]);
            
            // Mark coupon as used if applied
            if ($appliedCoupon) {
                $appliedCoupon->markAsUsed($order);
            }
            
            // Send notification to Customer
            Notification::createForCustomerOrderPlaced($order);
            
            // Notify sellers about the new order
            $order->notifySellersNewOrder();
            
            // AI Prediction and Coupon Generation
            $aiCouponService = new AICouponService();
            $couponResult = $aiCouponService->predictAndHandleCoupon($order, $freightCost, $deliveryTimeHours);
            
            if ($couponResult['coupon_generated']) {
                Log::info('AI-generated coupon created for order', [
                    'order_id' => $order->id,
                    'coupon_id' => $couponResult['coupon']->id,
                    'reason' => $couponResult['reason']
                ]);
            }
            
            DB::commit();
            Log::info('Order transaction committed successfully');
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Order creation failed', ['error' => $e->getMessage(), 'trace' => $e->getTraceAsString()]);
            return redirect()->route('cart.index')->with('flash.banner', 'An error occurred while placing your order. Please try again.')->with('flash.bannerStyle', 'danger');
        }
        
        // Clear cart
        Session::forget('cart');
        
        return redirect()->route('orders.show', $order)->with('flash.banner', 'Order placed successfully!')->with('flash.bannerStyle', 'success');
    }

    /**
     * Display order creation form.
     */
    public function create()
    {
        $cart = Session::get('cart', []);
        
        if (empty($cart)) {
            return redirect()->route('cart.index')->with('flash.banner', 'Your cart is empty!')->with('flash.bannerStyle', 'danger');
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
        
        // Calculate freight cost using TravelEstimatorService
        $travelEstimator = new \App\Services\TravelEstimatorService();
        $mockOrder = new \stdClass();
        $mockOrder->customer = Auth::user();
        $mockOrder->products = $products;
        $mockOrder->total_amount = $total;
        $mockOrder->total_items = array_sum(array_values($cart));
        $freightData = $travelEstimator->estimateTravel($mockOrder);

        $freightCost = $freightData['total_freight_cost'] ?? 0;
        
        // Log freight cost calculation
        Log::info('Freight cost calculated for mock order', [
            'total_amount' => $total,
            'freight_cost' => $freightCost,
            'total_with_freight' => $total + $freightCost
        ]);

        // Get AI prediction for satisfaction and potentially generate coupon
        $aiCouponService = new \App\Services\AICouponService();
        $predictionResult = $aiCouponService->predictAndGenerateCoupon($mockOrder, $freightCost);
        
        // Log prediction result
        Log::info('AI prediction result for mock order', [
            'prediction' => $predictionResult['prediction'] ?? null,
            'confidence' => $predictionResult['confidence'] ?? null,
            'coupon_generated' => isset($predictionResult['coupon']),
            'coupon_code' => $predictionResult['coupon']->code ?? null
        ]);

        // Store AI-generated coupon in session if created
        if (isset($predictionResult['coupon'])) {
            session(['ai_generated_coupon' => $predictionResult['coupon']]);
            Log::info('AI-generated coupon stored in session', [
                'coupon_code' => $predictionResult['coupon']->code,
                'discount_amount' => $predictionResult['coupon']->discount_amount,
                'min_order_amount' => $predictionResult['coupon']->min_order_amount
            ]);
        }

        // Check if there's a coupon in session
        $coupon = session('coupon');

        // Check if AI-generated coupon exists in session or elsewhere
        $aiGeneratedCoupon = session('ai_generated_coupon') ?? null;
        
        return view('orders.create', compact('cartItems', 'total', 'coupon', 'freightCost', 'aiGeneratedCoupon'));
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
            // Use database transaction to ensure data consistency
            try {
                DB::beginTransaction();
                Log::info('Starting order cancellation', ['order_id' => $order->id]);
                
                // Restore stock quantities for all items in the order
                foreach ($order->items as $item) {
                    $product = Product::find($item['product_id']);
                    if ($product) {
                        Log::info('Restoring stock for product', [
                            'product_id' => $item['product_id'],
                            'quantity_restored' => $item['quantity'],
                            'old_stock' => $product->stock_quantity,
                            'new_stock' => $product->stock_quantity + $item['quantity']
                        ]);
                        $product->stock_quantity += $item['quantity'];
                        $product->save();
                    } else {
                        Log::warning('Product not found during cancellation', ['product_id' => $item['product_id']]);
                    }
                }
                
                $order->update(['status' => 'cancelled']);
                Log::info('Order cancelled successfully', ['order_id' => $order->id]);
                
                // Notify customer about cancellation
                $order->notifyCustomerStatusChange('cancelled');
                
                // Notify sellers about order cancellation
                $order->notifySellersOrderCancellation();
                
                DB::commit();
                return redirect()->back()->with('flash.banner', 'Order cancelled successfully! Stock quantities have been restored.')->with('flash.bannerStyle', 'success');
            } catch (\Exception $e) {
                DB::rollBack();
                Log::error('Order cancellation failed', ['order_id' => $order->id, 'error' => $e->getMessage()]);
                return redirect()->back()->with('flash.banner', 'An error occurred while cancelling your order. Please try again.')->with('flash.bannerStyle', 'danger');
            }
        }
        
        Log::warning('Cannot cancel order - not in pending status', ['order_id' => $order->id, 'status' => $order->status]);
        return redirect()->back()->with('flash.banner', 'Cannot cancel this order!')->with('flash.bannerStyle', 'danger');
    }
}
