<meta charset="utf-8" />
<meta name="viewport" content="width=device-width, initial-scale=1.0" />

<title>{{ isset($title) ? $title.' · '.config('app.name') : config('app.name') }}</title>

<link rel="preconnect" href="https://fonts.bunny.net">
<link href="https://fonts.bunny.net/css?family=inter:400,500,600,700&display=swap" rel="stylesheet" />

@vite(['resources/css/app.css', 'resources/js/app.js'])

{{-- Storefront and auth pages are always light; the dashboard follows the user's appearance setting. --}}
@unless ($forceLight ?? false)
    @fluxAppearance
@endunless
