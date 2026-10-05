<?php

namespace App\Http\Middleware;

use App\Enums\SellerStatus;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureApprovedSeller
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if ($user->isApprovedSeller()) {
            return $next($request);
        }

        abort_if($user->sellerProfile?->status === SellerStatus::Suspended, 403, 'Your seller account is suspended.');

        // Non-sellers and pending applicants go to the application page.
        return redirect()->route('become-seller');
    }
}
