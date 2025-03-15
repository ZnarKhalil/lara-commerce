<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\OrderRequest;
use App\Models\Order;
use App\Models\Product;
use App\Enums\OrderStatus;
use App\Enums\PaymentStatus;
use Gate;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\DB;
use App\Http\Requests\UpdateOrderStatusRequest;

class OrderController extends Controller
{
    public function index(Request $request)
    {
        $orders = Order::query()
            ->when(!$request->user()->isAdmin(), function ($query) use ($request) {
                $query->where('user_id', $request->user()->id);
            })
            ->with('orderItems.product')
            ->latest()
            ->paginate(15);

        return $this->successResponse($orders);
    }

    public function store(OrderRequest $request)
    {
        $validated = $request->validated();

        try {
            DB::beginTransaction();

            // Calculate total and check stock
            $total = 0;
            $items = [];
            foreach ($validated['items'] as $item) {
                $product = Product::findOrFail($item['product_id']);
                
                if ($product->stock < $item['quantity']) {
                    throw new \Exception("Insufficient stock for product: {$product->name}");
                }

                $total += $product->price * $item['quantity'];
                $items[] = [
                    'product' => $product,
                    'quantity' => $item['quantity']
                ];
            }

            // Create order
            $order = Order::create([
                'user_id' => $request->user()->id,
                'order_number' => 'ORD-' . Str::random(10),
                'total_amount' => $total,
                'status' => OrderStatus::PENDING,
                'payment_status' => PaymentStatus::PENDING,
                'payment_method' => $validated['payment_method'],
                'shipping_address' => json_encode($validated['shipping_address']),
                'billing_address' => json_encode($validated['billing_address'])
            ]);

            // Create order items and update stock
            foreach ($items as $item) {
                $order->orderItems()->create([
                    'product_id' => $item['product']->id,
                    'quantity' => $item['quantity'],
                    'price' => $item['product']->price
                ]);

                $item['product']->decrement('stock', $item['quantity']);
            }

            DB::commit();

            $order->load('orderItems.product');
            return $this->successResponse($order, 'Order created successfully', 201);

        } catch (\Exception $e) {
            DB::rollBack();
            return $this->errorResponse($e->getMessage());
        }
    }

    public function show(Order $order)
    {
        Gate::authorize('view', $order);
        $order->load('orderItems.product');
        return $this->successResponse($order);
    }

    public function cancel(Order $order)
    {
        Gate::authorize('cancel', $order);

        if ($order->status !== OrderStatus::PENDING) {
            return $this->errorResponse('Only pending orders can be cancelled');
        }

        try {
            DB::beginTransaction();

            // Restore stock
            foreach ($order->orderItems as $item) {
                $item->product->increment('stock', $item->quantity);
            }

            $order->update(['status' => OrderStatus::CANCELLED]);

            DB::commit();

            return $this->successResponse($order, 'Order cancelled successfully');

        } catch (\Exception $e) {
            DB::rollBack();
            return $this->errorResponse($e->getMessage());
        }
    }

    public function track(Order $order)
    {
        Gate::authorize('view', $order);
        
        $trackingInfo = [
            'order_number' => $order->order_number,
            'status' => $order->status,
            'payment_status' => $order->payment_status,
            'created_at' => $order->created_at,
            'updated_at' => $order->updated_at
        ];

        return $this->successResponse($trackingInfo);
    }

    public function updateStatus(UpdateOrderStatusRequest $request, Order $order)
    {
        Gate::authorize('updateStatus', $order);

        $order->update(['status' => $request->validated('status')]);

        return $this->successResponse($order, 'Order status updated successfully');
    }
}
