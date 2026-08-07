@props(['title' => null, 'description' => null])

<!DOCTYPE html>
<html lang="en-GB">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <title>{{ $title ?? config('frith.coming_soon.meta.title') }}</title>
    <meta name="description" content="{{ $description ?? config('frith.coming_soon.meta.description') }}">

    <meta property="og:type" content="website">
    <meta property="og:site_name" content="Frith">
    <meta property="og:title" content="{{ $title ?? config('frith.coming_soon.meta.title') }}">
    <meta property="og:description" content="{{ $description ?? config('frith.coming_soon.meta.description') }}">
    <meta property="og:url" content="{{ url()->current() }}">
    <meta property="og:image" content="{{ asset('brand/img/frith-hero-hillside.png') }}">
    <meta property="og:image:alt" content="{{ config('frith.coming_soon.hero_image_alt') }}">
    <meta name="twitter:card" content="summary_large_image">

    <link rel="icon" href="{{ asset('brand/logo/frith-logo-horizontal-fullcolour.svg') }}" type="image/svg+xml">
    <meta name="theme-color" content="#F8F4EE">

    {{-- Nothing here is a waiting-list page's business to know. --}}
    <meta name="referrer" content="strict-origin-when-cross-origin">

    @vite('resources/css/frith.css')
</head>
<body>
    <a class="skip-link" href="#main">Skip to content</a>

    <div class="page">
        <header class="site-header gutter">
            <a class="site-header__home" href="{{ route('coming-soon') }}">
                <img class="site-header__logo"
                     src="{{ asset('brand/logo/frith-logo-horizontal-fullcolour.svg') }}"
                     alt="Frith — home" width="490" height="155">
            </a>
            @isset($badge){{ $badge }}@endisset
        </header>

        {{ $slot }}

        <footer class="site-footer gutter">
            <p>&copy; {{ date('Y') }} {{ config('frith.company.name') }} &middot; {{ config('frith.company.location') }}</p>
            <p class="site-footer__links">
                <a href="mailto:{{ config('frith.company.contact_email') }}">{{ config('frith.company.contact_email') }}</a>
                <a href="#privacy">Privacy</a>
                <a href="#safeguarding">Safeguarding</a>
            </p>
        </footer>
    </div>
</body>
</html>
