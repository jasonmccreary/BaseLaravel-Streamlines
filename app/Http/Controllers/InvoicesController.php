<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class InvoicesController extends Controller
{
    public function store(Request $request)
    {
        if (!Auth::check()) {
            abort(401);
        }

        $request->validate([
            'order_id' => 'required|exists:orders,id',
            'billing_details' => 'required|string',
            'tax_id' => 'nullable|string',
        ]);

        $order = Order::find($request->input('order_id'));
        if ($order->user_id !== Auth::id()) {
            abort(403);
        }

        $invoice = Invoice::create($request->only([
            'order_id',
            'billing_details',
            'tax_id',
        ]));

        return redirect()->route('invoice.show', $invoice);
    }
}
