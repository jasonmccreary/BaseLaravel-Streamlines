<?php

namespace App\Http\Controllers;

use App\Jobs\PerformShift;
use App\Order;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Stripe\Stripe;

class CheckoutController extends Controller
{
    public function store(Request $request, Order $order)
    {
        try {
            $this->verifyOrderBelongsToUser($order);

            Stripe::setApiKey(config('services.stripe.secret'));

            if ($request->has('save-cc')) {
                if (!$request->user()->hasStripeId()) {
                    $request->user()->createAsStripeCustomer();
                }

                $request->user()->updateCard($request->input('stripeToken'));
            }

            if ($order->user->hasCardOnFile() && ($request->has('use-saved-card') || $request->has('save-cc'))) {
                $charge = $order->user->charge($order->totalInCents(), [
                    'currency' => 'usd',
                    'description' => $order->product->name,
                    'receipt_email' => $order->user->email,
                    'metadata' => ['order_id' => $order->id],
                ]);
            } else {
                $charge = \Stripe\Charge::create([
                    'amount' => $order->totalInCents(),
                    'currency' => 'usd',
                    'source' => $request->input('stripeToken'),
                    'description' => $order->product->name,
                    'receipt_email' => $order->user->email,
                    'metadata' => ['order_id' => $order->id],
                ]);
            }

            $order->stripe_id = $charge->id;
            $order->status = Order::STATUS_PAID;
            $order->save();

            event('shift.purchased', $order);

            PerformShift::dispatch($order);
        } catch (\Stripe\Error\Card $e) {
            $data = $e->getJsonBody();
            Log::error('Card failed: ', $data);
            $template = 'partials.errors.charge_failed';
            $data = $data['error'];

            return redirect()->back()->withInput($request->input())->with('error', compact('template', 'data'));
        } catch (\PDOException $e) {
            Log::error($e);

            return redirect()->back()->withInput($request->input())->with(
                'error',
                ['template' => 'partials.errors.order_save_failed']
            );
        } catch (\Exception $e) {
            Log::error($e);
            $template = 'partials.errors.order_unknown_failure';
            $data = ['code' => $e->getCode()];

            return redirect()->back()->withInput($request->input())->with('error', compact('template', 'data'));
        }

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
