<?php

namespace App\Http\Controllers;

use App\Mail\AlertCustomerMail;
use App\Models\Alert;
use App\Models\Order;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;

class AlertController extends Controller
{
    public function send(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'order_id' => ['required', 'integer', 'exists:orders,id'],
        ]);

        $order = Order::query()
            ->with(['customer', 'items.medication'])
            ->findOrFail($validated['order_id']);

        $customer = $order->customer;

        if (! $customer || ! $customer->email) {
            return response()->json([
                'message' => 'Customer email is not available for this order.',
            ], 422);
        }

        $lotNumbers = $order->items
            ->pluck('medication')
            ->filter()
            ->unique('id')
            ->pluck('lot_number')
            ->unique()
            ->values()
            ->implode(', ');

        if ($lotNumbers === '') {
            return response()->json([
                'message' => 'Order has no medications to reference in the alert.',
            ], 422);
        }

        $warningMessage = __('We are contacting you regarding a safety matter linked to medication included in one of your orders.');
        $recommendationMessage = __('Please stop using the affected product and contact your pharmacist or physician for guidance. If you experience adverse effects, seek medical advice without delay.');

        Mail::to($customer->email)->send(new AlertCustomerMail(
            customerName: $customer->name,
            warningMessage: $warningMessage,
            medicationLotNumber: $lotNumbers,
            recommendationMessage: $recommendationMessage,
        ));

        Alert::query()->create([
            'customer_id' => $customer->id,
            'order_id' => $order->id,
            'sent_at' => now(),
        ]);

        return response()->json([
            'message' => 'Alert sent successfully',
        ]);
    }
}
