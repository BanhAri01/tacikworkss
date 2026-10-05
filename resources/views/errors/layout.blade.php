<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex">
    <title>{{ $code }} · {{ config('app.name') }}</title>
    {{-- Self-contained on purpose: must render even when the DB, session or asset build is broken. --}}
    <style>
        :root { color-scheme: light dark; --bg: #ffffff; --fg: #18181b; --muted: #71717a; --btn: #18181b; --btn-fg: #ffffff; }
        @media (prefers-color-scheme: dark) { :root { --bg: #18181b; --fg: #fafafa; --muted: #a1a1aa; --btn: #fafafa; --btn-fg: #18181b; } }
        body { margin: 0; min-height: 100vh; display: grid; place-items: center; background: var(--bg); color: var(--fg); font: 16px/1.5 system-ui, -apple-system, "Segoe UI", sans-serif; }
        main { padding: 24px 16px; max-width: 420px; text-align: center; }
        .code { font-size: 64px; font-weight: 700; letter-spacing: -2px; margin: 0; }
        h1 { font-size: 20px; margin: 8px 0; }
        p { color: var(--muted); margin: 0 0 24px; }
        a.btn { display: inline-block; background: var(--btn); color: var(--btn-fg); padding: 10px 18px; border-radius: 8px; text-decoration: none; font-weight: 600; font-size: 14px; }
        form { margin-top: 16px; display: flex; gap: 8px; }
        input { flex: 1; min-width: 0; padding: 10px 12px; border: 1px solid var(--muted); border-radius: 8px; background: transparent; color: inherit; }
    </style>
</head>
<body>
    <main>
        <p class="code">{{ $code }}</p>
        <h1>{{ $title }}</h1>
        <p>{{ $message }}</p>
        <a class="btn" href="{{ url('/') }}">{{ __('Back to the marketplace') }}</a>
        @if ($code === 404)
            <form action="{{ url('/search') }}" method="GET" role="search">
                <input type="search" name="q" placeholder="{{ __('Search designs…') }}" aria-label="{{ __('Search') }}">
            </form>
        @endif
    </main>
</body>
</html>
