<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Order;
use App\Helpers\ApiFormatter;
use Illuminate\Support\Facades\Validator;

class OrderController extends Controller
{
    public function index()
    {
        $user = auth('api')->user();

        // Customer sees their own orders, Admin sees all
        if ($user->role === 'admin') {
            $orders = Order::orderBy('created_at', 'DESC')->get();
        } else {
            $orders = Order::where('user_id', $user->id)->orderBy('created_at', 'DESC')->get();
        }

        return ApiFormatter::createJson(200, 'Get Orders Success', $orders);
    }

    public function store(Request $request)
    {
        // This is a basic create order method just to test the table.
        // The real checkout logic will be implemented later when order_items exist.
        $user = auth('api')->user();

        $order = Order::create([
            'user_id' => $user->id,
            'order_date' => now(),
            'total_amount' => $request->input('total_amount', 0),
            'status' => 'pending'
        ]);

        return ApiFormatter::createJson(201, 'Create Order Success', $order);
    }

    public function show($id)
    {
        $user = auth('api')->user();
        $order = Order::find($id);

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
