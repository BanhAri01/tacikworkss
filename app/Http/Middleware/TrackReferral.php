<?php

namespace App\Http\Middleware;

use App\Support\Attribution;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/** `?ref=username` credits a seller, `?aff=code` credits an affiliate, both for 30 days. */
class TrackReferral
{
    public function handle(Request $request, Closure $next): Response
    {
        foreach (['ref' => 'remember', 'aff' => 'rememberAffiliate'] as $param => $method) {
            $value = $request->query($param);

            if (is_string($value) && $value !== '' && strlen($value) <= 30) {
                Attribution::$method($value);
            }
        }

        return $next($request);
    }
}
