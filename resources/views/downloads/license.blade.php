<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>License certificate {{ $item->purchase_code }}</title>
    <style>
        body { font-family: DejaVu Sans, sans-serif; color: #18181b; font-size: 12px; margin: 40px; }
        h1 { font-size: 24px; margin: 0 0 4px; }
        .muted { color: #71717a; }
        table { width: 100%; border-collapse: collapse; margin-top: 24px; }
        td { padding: 8px 0; border-bottom: 1px solid #e4e4e7; vertical-align: top; }
        td.label { width: 32%; color: #71717a; }
        .terms { margin-top: 24px; padding: 16px; background: #f4f4f5; }
        .code { font-family: DejaVu Sans Mono, monospace; font-size: 13px; }
    </style>
</head>
<body>
    <h1>License certificate</h1>
    <p class="muted">{{ config('app.name') }} · {{ url('/') }}</p>

    <table>
        <tr><td class="label">Item</td><td>{{ $item->product_title }}</td></tr>
        <tr><td class="label">License</td><td>{{ $item->license_name }}</td></tr>
        <tr><td class="label">Licensee</td><td>{{ $item->order->buyer->name }} ({{ $item->order->buyer->email }})</td></tr>
        <tr><td class="label">Author</td><td>{{ $item->seller->sellerProfile?->display_name ?? $item->seller->name }}</td></tr>
        <tr><td class="label">Purchase date</td><td>{{ $item->order->paid_at?->toDayDateTimeString() }} (UTC)</td></tr>
        <tr><td class="label">Purchase code</td><td class="code">{{ $item->purchase_code }}</td></tr>
        <tr><td class="label">Order</td><td class="code">{{ $item->order->uuid }}</td></tr>
    </table>

    <div class="terms">
        <strong>{{ $item->license_name }} license terms</strong>
        <p>{{ $item->license?->description }}</p>
        <p class="muted">This certificate confirms the licensee above holds a non-transferable license for the item under the terms shown. Keep it with your project records.</p>
    </div>
</body>
</html>
