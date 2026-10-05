<?php

namespace App\Http\Controllers;

use App\Contracts\PaymentGateway;
use App\Jobs\HandlePayPalEvent;
use App\Models\GatewayEvent;
use Illuminate\Http\Request;

class PayPalWebhookController extends Controller
{
    public function __invoke(Request $request, PaymentGateway $gateway)
    {
        if (! $gateway->verifyWebhook($request)) {
            return response('invalid signature', 400);
        }

        $payload = $request->json()->all();
        $eventId = (string) ($payload['id'] ?? '');

        if ($eventId === '') {
            return response('missing id', 400);
        }

        // PayPal retries deliveries; the unique event id makes processing idempotent.
        $event = GatewayEvent::firstOrCreate(
            ['gateway' => 'paypal', 'event_id' => $eventId],
            ['type' => (string) ($payload['event_type'] ?? ''), 'payload' => $payload],
        );

        if (! $event->processed_at) {
            HandlePayPalEvent::dispatch($event->id);
        }

        return response('ok');
    }
}
