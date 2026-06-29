<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use App\Helpers\ApiFormatter;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;

class OrderController extends Controller
{
    public function index()
    {
        $user = auth('api')->user();

        // Customer sees their own orders, Admin sees all
        if ($user->role === 'admin') {
            $orders = Order::with('orderItems.product')->orderBy('created_at', 'DESC')->get();
        } else {
            $orders = Order::with('orderItems.product')->where('user_id', $user->id)->orderBy('created_at', 'DESC')->get();
        }

        return ApiFormatter::createJson(200, 'Get Orders Success', $orders);
    }

    public function store(Request $request)
    {
        $user = auth('api')->user();

        $validator = Validator::make($request->all(), [
            'items' => 'required|array|min:1',
            'items.*.product_id' => 'required|exists:products,id',
            'items.*.quantity' => 'required|integer|min:1',
        ]);

        if ($validator->fails()) {
            return ApiFormatter::createJson(400, 'Bad Request', $validator->errors()->all());
        }

        try {
            DB::beginTransaction();

            $totalAmount = 0;
            $orderItemsData = [];

            // 1. Loop through items to check stock and calculate total
            foreach ($request->items as $item) {
                // We use lockForUpdate() to prevent race conditions when multiple users buy at the same time
                $product = Product::lockForUpdate()->find($item['product_id']);

                if ($product->stock < $item['quantity']) {
                    DB::rollBack();
                    return ApiFormatter::createJson(400, 'Bad Request', 'Stok produk ' . $product->name . ' tidak mencukupi. Sisa stok: ' . $product->stock);
                }

                $totalAmount += ($product->price * $item['quantity']);

                $orderItemsData[] = [
                    'product' => $product,
                    'quantity' => $item['quantity'],
                    'price_per_item' => $product->price
                ];
            }

            // 2. Create the Order (Nota Utama)
            $order = Order::create([
                'user_id' => $user->id,
                'order_date' => now(),
                'total_amount' => $totalAmount,
                'status' => 'pending'
            ]);

            // 3. Create Order Items & Deduct Stock
            foreach ($orderItemsData as $data) {
                OrderItem::create([
                    'order_id' => $order->id,
                    'product_id' => $data['product']->id,
                    'quantity' => $data['quantity'],
                    'price_per_item' => $data['price_per_item']
                ]);

                // Deduct stock
                $data['product']->decrement('stock', $data['quantity']);
            }

            DB::commit();

            return ApiFormatter::createJson(201, 'Checkout Success', $order->load('orderItems.product'));

        } catch (\Exception $e) {
            DB::rollBack();
            return ApiFormatter::createJson(500, 'Internal Server Error', $e->getMessage());
        }
    }

    public function show($id)
    {
        $user = auth('api')->user();
        $order = Order::with('orderItems.product')->find($id);

        if (is_null($order)) {
            return ApiFormatter::createJson(404, 'Order Not Found');
        }

        if ($user->role !== 'admin' && $order->user_id !== $user->id) {
            return ApiFormatter::createJson(403, 'Forbidden', 'You are not allowed to view this order');
        }

        return ApiFormatter::createJson(200, 'Get Detail Order Success', $order);
    }

    public function updateStatus(Request $request, $id)
    {
        $user = auth('api')->user();
        $order = Order::find($id);
        
        if (is_null($order)) {
            return ApiFormatter::createJson(404, 'Order Not Found');
        }

        $validator = Validator::make($request->all(), [
            'status' => 'required|in:pending,paid,shipped'
        ]);

        if ($validator->fails()) {
            return ApiFormatter::createJson(400, 'Bad Request', $validator->errors()->all());
        }

        $newStatus = $request->input('status');

        // Customer can only pay, Admin can do anything (e.g. ship)
        if ($user->role === 'customer' && !in_array($newStatus, ['pending', 'paid'])) {
            return ApiFormatter::createJson(403, 'Forbidden', 'Customer can only change status to paid');
        }

        if ($user->role === 'customer' && $order->user_id !== $user->id) {
            return ApiFormatter::createJson(403, 'Forbidden', 'This is not your order');
        }

        $order->update(['status' => $newStatus]);
        return ApiFormatter::createJson(200, 'Update Order Status Success', $order->fresh());
    }
}
