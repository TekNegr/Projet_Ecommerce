<?php

namespace App\Http\Controllers\Seller;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class OrderController extends Controller
{
    /**
     * Display seller orders dashboard.
     */
    public function dashboard()
    {
        return view('seller.orders');
    }

    /**
     * Display a listing of the seller's orders.
     */
    public function index()
    {
        $sellerId = Auth::id();

        // Show only orders that contain items from this seller
        $orders = Order::whereJsonContains('seller_ids', $sellerId)
            ->latest()
            ->paginate(10);

        return view('seller.orders.index', compact('orders'));
    }

    /**
     * Display the specified order.
     */
    public function show(Order $order)
    {
        $sellerId = Auth::id();
        
        // Check if the seller has items in this order
        if (!in_array($sellerId, $order->seller_ids ?? [])) {
            abort(403, 'Unauthorized access to this order.');
        }
        
        return view('seller.orders.show', compact('order'));
    }

    /**
     * Handle Continue action - progress order status
     */
    public function continue(Order $order)
    {
        $sellerId = Auth::id();
        
        // Check if the seller has items in this order
        if (!in_array($sellerId, $order->seller_ids ?? [])) {
            abort(403, 'Unauthorized access to this order.');
        }
        
        $currentStatus = $order->status;
        $newStatus = $this->getNextStatus($currentStatus);
        
        // If status is changing to shipped, mark all seller's items as shipped
        if ($newStatus === 'shipped') {
            $this->markSellerItemsAsShipped($order);
        }
        
        $order->update(['status' => $newStatus]);
        
        // Notify customer about status change
        $order->notifyCustomerStatusChange($newStatus);
        
        // Check if all items are shipped to update to delivered
        if ($this->areAllItemsShipped($order)) {
            $order->update(['status' => 'delivered']);
            // Notify customer about delivery
            $order->notifyCustomerStatusChange('delivered');
        }

        return redirect()->route('seller.orders.index')
            ->with('success', 'Order status progressed successfully.');
    }

    /**
     * Handle Cancel action - remove seller's items from order
     */
    public function cancel(Order $order)
    {
        $sellerId = Auth::id();
        
        // Check if the seller has items in this order
        if (!in_array($sellerId, $order->seller_ids ?? [])) {
            abort(403, 'Unauthorized access to this order.');
        }
        
        try {
            DB::beginTransaction();
            
            // Remove seller's items from the order
            $this->removeSellerItems($order, $sellerId);
            
            // Check if all items are removed to cancel the entire order
            if ($this->areAllItemsRemoved($order)) {
                $order->update(['status' => 'cancelled']);
                // Notify customer about cancellation
                $order->notifyCustomerStatusChange('cancelled');
            } else {
                // Notify customer that seller items were removed
                \App\Models\Notification::create([
                    'user_id' => $order->user_id,
                    'order_id' => $order->id,
                    'message' => 'Some items from order #' . $order->id . ' have been removed by the seller.',
                    'type' => 'customer',
                ]);
            }

            DB::commit();
            
            return redirect()->route('seller.orders.index')
                ->with('success', 'Your items have been removed from the order.');
                
        } catch (\Exception $e) {
            DB::rollBack();
            return redirect()->route('seller.orders.index')
                ->with('error', 'Failed to remove items from order.');
        }
    }

    /**
     * Get next status in progression
     */
    private function getNextStatus($currentStatus)
    {
        $statusFlow = [
            'pending' => 'processing',
            'processing' => 'shipped',
            'shipped' => 'shipped', // Stay at shipped until all items are delivered
            'delivered' => 'delivered',
            'cancelled' => 'cancelled'
        ];
        
        return $statusFlow[$currentStatus] ?? 'pending';
    }

    /**
     * Mark all seller's items as shipped
     */
    private function markSellerItemsAsShipped(Order $order)
    {
        $sellerId = Auth::id();
        $items = $order->items ?? [];
        $itemsShipped = $order->items_shipped ?? [];
        
        foreach ($items as $item) {
            if ($item['seller_id'] == $sellerId) {
                $itemsShipped[] = $item['product_id'];
            }
        }
        
        // Remove duplicates and ensure unique product_ids
        $itemsShipped = array_unique($itemsShipped);
        $order->update(['items_shipped' => array_values($itemsShipped)]);
    }

    /**
     * Remove seller's items from order
     */
    private function removeSellerItems(Order $order, $sellerId)
    {
        $items = $order->items ?? [];
        $remainingItems = [];
        $newTotalAmount = 0;
        
        foreach ($items as $item) {
            if ($item['seller_id'] != $sellerId) {
                $remainingItems[] = $item;
                $newTotalAmount += $item['price'] * $item['quantity'];
                
                // Restore stock for removed items
                $product = Product::find($item['product_id']);
                if ($product) {
                    $product->stock_quantity += $item['quantity'];
                    $product->save();
                }
            }
        }
        
        $order->update([
            'items' => $remainingItems,
            'total_amount' => $newTotalAmount
        ]);
    }

    /**
     * Check if all items are shipped
     */
    private function areAllItemsShipped(Order $order)
    {
        $itemsShipped = $order->items_shipped ?? [];
        $items = $order->items ?? [];
        
        if (empty($items)) {
            return false;
        }
        
        // Check if all product_ids in items are in items_shipped
        foreach ($items as $item) {
            if (!in_array($item['product_id'], $itemsShipped)) {
                return false;
            }
        }
        
        return true;
    }

    /**
     * Check if all items are removed
     */
    private function areAllItemsRemoved(Order $order)
    {
        return empty($order->items);
    }
}
