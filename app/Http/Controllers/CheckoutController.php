<?php

namespace App\Http\Controllers;

use App\Jobs\PerformShift;
use App\Order;
use Illuminate\Http\Request;
use Stripe\Stripe;

class CheckoutController extends Controller
{
    public function store(Request $request, Order $order, PaymentGateway $paymentGateway)
    {
        $this->verifyOrderBelongsToUser($order);

        $order->markAsPaid($paymentGateway->charge($request, $order));

        event('shift.purchased', $order);

        $this->userNotification($order);

        return redirect()->to('/account');
    }

    private function userNotification(Order $order)
    {
        if ($order->isFirstOrderForUser()) {
            request()->session()->flash('first-run');

            return;
        }

        if ($order->product->isOlderLaravelShift()) {
            request()->session()->flash('older-run');

            return;
        }
    }

    private function verifyOrderBelongsToUser($order)
    {
        if (auth()->id() != $order->user_id) {
            abort(403);
        }
    }
}
