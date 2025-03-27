<?php

namespace App\Http\Controllers\Api;

use App\Enums\OrderStatus;
use App\Enums\PaymentStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\OrderRequest;
use App\Http\Requests\UpdateOrderStatusRequest;
use App\Http\Resources\OrderResource;
use App\Http\Resources\OrderStatusResource;
use App\Models\Order;
use App\Models\Product;
use Gate;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class OrderController extends Controller
{
    public function index(Request $request)
    {
        $orders = Order::query()
            ->when(! $request->user()->isAdmin(), function ($query) use ($request) {
                $query->where('user_id', $request->user()->id);
            })
            ->with('orderItems.product')
            ->when($request->user()->isAdmin(), function ($query) {
                $query->with('user');
            })
            ->latest()
            ->paginate(15);

        return $this->successResponse([
            'data' => OrderResource::collection($orders),
            'current_page' => $orders->currentPage(),
            'per_page' => $orders->perPage(),
            'total' => $orders->total(),
            'last_page' => $orders->lastPage(),
        ]);
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
                    'quantity' => $item['quantity'],
                ];
            }

            // Create order
            $order = Order::create([
                'user_id' => $request->user()->id,
                'order_number' => 'ORD-'.Str::random(10),
                'total_amount' => $total,
                'status' => OrderStatus::PENDING,
                'payment_status' => PaymentStatus::PENDING,
                'payment_method' => $validated['payment_method'],
                'shipping_address' => json_encode($validated['shipping_address']),
                'billing_address' => json_encode($validated['billing_address']),
            ]);

            // Create order items and update stock
            foreach ($items as $item) {
                $order->orderItems()->create([
                    'product_id' => $item['product']->id,
                    'quantity' => $item['quantity'],
                    'price' => $item['product']->price,
                ]);

                $item['product']->decrement('stock', $item['quantity']);
            }

            DB::commit();

            $order->load('orderItems.product');

            return $this->successResponse(
                new OrderResource($order),
                'Order created successfully',
                201
            );

        } catch (\Exception $e) {
            DB::rollBack();

            return $this->errorResponse($e->getMessage());
        }
    }

    public function show(Order $order)
    {
        Gate::authorize('view', $order);
        
        $order->load('orderItems.product')
            ->when(request()->user()->isAdmin(), function ($query) {
                $query->load('user');
            });

        return $this->successResponse(new OrderResource($order));
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
            
            $order->load('orderItems.product');

            DB::commit();

            return $this->successResponse(
                new OrderResource($order),
                'Order cancelled successfully'
            );

        } catch (\Exception $e) {
            DB::rollBack();

            return $this->errorResponse($e->getMessage());
        }
    }

    // public function track(Order $order)
    // {
    //     Gate::authorize('view', $order);

    //     $trackingInfo = [
    //         'order_number' => $order->order_number,
    //         'status' => $order->status,
    //         'payment_status' => $order->payment_status,
    //         'created_at' => $order->created_at,
    //         'updated_at' => $order->updated_at,
    //     ];

    //     return $this->successResponse($trackingInfo);
    // }

    public function updateStatus(UpdateOrderStatusRequest $request, Order $order)
    {
        Gate::authorize('updateStatus', $order);

        $order->update(['status' => $request->validated('status')]);
        
        

        return $this->successResponse(
            new OrderStatusResource($order),
            'Order status updated successfully'
        );
    }
}
