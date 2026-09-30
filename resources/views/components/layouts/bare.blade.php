@props(['title' => null, 'description' => null])

{{-- The chrome for a screen with nothing to click away to: the registration
     form, and logging in. The hi-fi strips the header to the wordmark on both —
     somebody half way through registering, or trying to get in, should not be
     sitting under a menu of ways to leave.

     The footer stays whole. The designs give the registration screens a single
     line of small print instead, but Matt preferred the full one and it is the
     kinder choice anyway: a person stuck on either of these screens is exactly
     who needs the help centre and a way to reach a human. --}}
<!DOCTYPE html>
<html lang="en-GB">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $title }}</title>
    @if ($description)<meta name="description" content="{{ $description }}">@endif
    <meta name="robots" content="noindex, nofollow">
    <meta name="theme-color" content="#F8F4EE">
    <meta name="color-scheme" content="light">

    <link rel="preload" href="{{ \App\Support\BrandAsset::url('fonts/livvic-400-latin.woff2') }}" as="font" type="font/woff2" crossorigin>
    <link rel="preload" href="{{ \App\Support\BrandAsset::url('fonts/livvic-600-latin.woff2') }}" as="font" type="font/woff2" crossorigin>
    <link rel="icon" href="{{ \App\Support\BrandAsset::url('brand/logo/frith-icon.svg') }}" type="image/svg+xml">

    @vite('resources/scss/main.scss')
</head>
<body class="bare-page">

    <a class="skip-link" href="#main">Skip to content</a>

    <header class="bare-header">
        <div class="bare-header__inner">
            <a href="{{ route('home') }}">
                <img class="bare-header__logo"
                     src="{{ \App\Support\BrandAsset::url('brand/logo/frith-logo-horizontal-fullcolour.svg') }}"
                     alt="Frith — home" width="1152" height="464">
            </a>
        </div>
    </header>

    <main id="main">{{ $slot }}</main>

    <x-site.footer />

</body>
</html>
