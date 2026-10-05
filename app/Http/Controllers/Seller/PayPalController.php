<?php

namespace App\Http\Controllers\Seller;

use App\Actions\Seller\RefreshPayPalStatus;
use App\Contracts\PaymentGateway;
use App\Http\Controllers\Controller;
use App\Payments\PaymentGatewayException;
use Illuminate\Http\Request;

class PayPalController extends Controller
{
    public function connect(Request $request, PaymentGateway $gateway)
    {
        try {
            return redirect()->away($gateway->onboardingUrl($request->user()->sellerProfile, route('seller.paypal.return')));
        } catch (PaymentGatewayException) {
            return back()->withErrors(['paypal' => __('PayPal is unavailable right now. Please try again later.')]);
        }
    }

    public function return(Request $request, RefreshPayPalStatus $refresh)
    {
        try {
            $profile = $refresh->handle($request->user()->sellerProfile);
        } catch (PaymentGatewayException) {
            return redirect()->route('seller.dashboard')->withErrors(['paypal' => __('We couldn\'t verify your PayPal account yet. Try "Check status" in a minute.')]);
        }

        return redirect()->route('seller.dashboard')->with('status', $profile->canReceivePayments()
            ? __('PayPal connected. You can now receive payments.')
            : __('PayPal linked, but it can\'t receive payments yet. Confirm your PayPal email and finish the PayPal setup, then check again.'));
    }
}
