<?php

namespace App\Http\Controllers;

use App\Models\Order;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class OrderController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        if (! $request->filled('lot')) {
            return response()->json([
                'message' => 'The lot query parameter is required.',
            ], 400);
        }

        $lot = $request->query('lot');

        $orders = Order::query()
            ->whereHas('items.medication', function ($query) use ($lot) {
                $query->where('lot_number', $lot);
            })
            ->when($request->filled('start_date'), function ($query) use ($request) {
                $query->whereDate('purchase_date', '>=', $request->query('start_date'));
            })
            ->when($request->filled('end_date'), function ($query) use ($request) {
                $query->whereDate('purchase_date', '<=', $request->query('end_date'));
            })
            ->with(['customer', 'items.medication'])
            ->orderBy('purchase_date')
            ->get();

        $data = $orders->map(function (Order $order) {
            return [
                'order_id' => $order->id,
                'purchase_date' => $order->purchase_date->format('Y-m-d'),
                'customer' => [
                    'name' => $order->customer->name,
                    'email' => $order->customer->email,
                    'phone' => $order->customer->phone,
                ],
                'medications' => $order->items
                    ->pluck('medication')
                    ->unique('id')
                    ->values()
                    ->map(fn ($medication) => [
                        'name' => $medication->name,
                        'lot_number' => $medication->lot_number,
                    ])
                    ->all(),
            ];
        });

        return response()->json($data);
    }
}
