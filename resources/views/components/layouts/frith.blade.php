@props([
    'title' => null,
    'description' => null,
    'noindex' => false,
])

@php
    $meta = \App\Support\PageContent::get('coming-soon', 'meta', []);
    $copy = \App\Support\PageContent::for('coming-soon');

    $pageTitle = $title ?? ($meta['title'] ?? config('frith.coming_soon.meta.title'));
    $pageDescription = $description ?? ($meta['description'] ?? config('frith.coming_soon.meta.description'));

    $heroUrl = ! empty($copy['hero_image'])
        ? \Illuminate\Support\Facades\Storage::url($copy['hero_image'])
        : asset('brand/img/frith-hero-hillside.png');
@endphp

<!DOCTYPE html>
<html lang="en-GB">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <title>{{ $pageTitle }}</title>
    <meta name="description" content="{{ $pageDescription }}">

    {{-- One canonical URL per page. Without it, a link with a stray ?utm_source
         is a second copy of the page as far as a crawler is concerned. --}}
    <link rel="canonical" href="{{ url()->current() }}">

    {{-- Confirm and unsubscribe pages are reached from a link in an email and
         have nothing to offer a search result, so they say so explicitly
         rather than relying on being unlinked. --}}
    @if ($noindex)
        <meta name="robots" content="noindex, nofollow">
    @else
        <meta name="robots" content="index, follow, max-image-preview:large, max-snippet:-1">
    @endif

    <meta name="author" content="{{ config('frith.company.name') }}">
    <meta name="theme-color" content="#F8F4EE">
    <meta name="color-scheme" content="light">
    <meta name="format-detection" content="telephone=no">

    {{-- Nothing about a waiting-list page is another site's business. --}}
    <meta name="referrer" content="strict-origin-when-cross-origin">

    {{-- Open Graph --}}
    <meta property="og:type" content="website">
    <meta property="og:site_name" content="Frith">
    <meta property="og:title" content="{{ $pageTitle }}">
    <meta property="og:description" content="{{ $pageDescription }}">
    <meta property="og:url" content="{{ url()->current() }}">
    <meta property="og:locale" content="en_GB">
    <meta property="og:image" content="{{ $heroUrl }}">
    <meta property="og:image:width" content="519">
    <meta property="og:image:height" content="340">
    <meta property="og:image:type" content="image/png">
    <meta property="og:image:alt" content="{{ $copy['hero_image_alt'] ?? '' }}">

    {{-- Twitter/X. No @site or @creator until the accounts exist — claiming
         handles that don't is worse than omitting them. --}}
    <meta name="twitter:card" content="summary_large_image">
    <meta name="twitter:title" content="{{ $pageTitle }}">
    <meta name="twitter:description" content="{{ $pageDescription }}">
    <meta name="twitter:image" content="{{ $heroUrl }}">
    <meta name="twitter:image:alt" content="{{ $copy['hero_image_alt'] ?? '' }}">

    {{-- Icons. 16 and 32 use the thickened variant from guidelines §02, because
         the standard hairline ring breaks up below 32px. --}}
    <link rel="icon" href="{{ asset('brand/logo/frith-icon.svg') }}" type="image/svg+xml">
    <link rel="icon" href="{{ asset('brand/icon/frith-icon-32.png') }}" sizes="32x32" type="image/png">
    <link rel="icon" href="{{ asset('brand/icon/frith-icon-16.png') }}" sizes="16x16" type="image/png">
    <link rel="apple-touch-icon" href="{{ asset('brand/icon/frith-icon-180.png') }}" sizes="180x180">
    <link rel="manifest" href="{{ route('manifest') }}">

    {{-- Poppins is self-hosted and render-blocking on first paint, so the two
         weights above the fold are preloaded. crossorigin is required on font
         preloads even same-origin, or the browser fetches them twice. --}}
    <link rel="preload" href="{{ asset('fonts/poppins-400-latin.woff2') }}" as="font" type="font/woff2" crossorigin>
    <link rel="preload" href="{{ asset('fonts/poppins-600-latin.woff2') }}" as="font" type="font/woff2" crossorigin>

    @vite('resources/css/frith.css')

    @unless ($noindex)
        <script type="application/ld+json">{!! json_encode(\App\Support\StructuredData::comingSoon(), JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) !!}</script>
    @endunless
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
            <p>&copy; {{ date('Y') }} {{ config('frith.company.name') }}</p>
            <p class="site-footer__links">
                <a href="mailto:{{ config('frith.company.contact_email') }}">{{ config('frith.company.contact_email') }}</a>
                <a href="#privacy">Privacy</a>
                <a href="#safeguarding">Safeguarding</a>
            </p>
        </footer>
    </div>
</body>
</html>
