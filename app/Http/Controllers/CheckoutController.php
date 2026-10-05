<?php

namespace App\Http\Controllers;

use App\Actions\Checkout\CompleteOrder;
use App\Actions\Checkout\StartCheckout;
use App\Contracts\PaymentGateway;
use App\Enums\OrderStatus;
use App\Models\Order;
use App\Payments\PaymentGatewayException;
use Illuminate\Http\Request;

class CheckoutController extends Controller
{
    public function start(Request $request, StartCheckout $checkout)
    {
        return redirect()->away($checkout->handle($request->user()));
    }

    /** Buyer approved the payment at PayPal and was sent back here: capture it. */
    public function return(Request $request, PaymentGateway $gateway, CompleteOrder $complete)
    {
        $order = $this->ownOrder($request);

        if (! $order->isPaid() && $order->status === OrderStatus::Pending) {
            try {
                $complete->handle($order, $gateway->capture($order->gateway_ref));
            } catch (PaymentGatewayException) {
                return redirect()->route('cart')->withErrors(['cart' => __('We couldn\'t confirm your payment yet. If you were charged, your items will appear in My downloads shortly.')]);
            }
        }

        $order->refresh();

        if (! $order->isPaid()) {
            return redirect()->route('cart')->withErrors(['cart' => __('Your payment is still being processed by PayPal. We\'ll email you when it completes.')]);
        }

        return redirect()->route('checkout.success', $order);
    }

    public function cancel(Request $request)
    {
        $order = $this->ownOrder($request);

        if ($order->status === OrderStatus::Pending) {
            $order->update(['status' => OrderStatus::Cancelled]);
        }

        return redirect()->route('cart')->with('status', __('Payment cancelled. Your cart is unchanged.'));
    }

    public function success(Request $request, Order $order)
    {
        abort_unless($order->buyer_id === $request->user()->id, 404);
        abort_unless($order->isPaid(), 404);

        return view('checkout.success', ['order' => $order->load('items')]);
    }

    private function ownOrder(Request $request): Order
    {
        $token = (string) $request->query('token');
        abort_if($token === '', 404);

        return Order::where('gateway_ref', $token)->where('buyer_id', $request->user()->id)->firstOrFail();
    }
}
