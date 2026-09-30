@props([
    'title' => null,
    'description' => null,
    'noindex' => false,
    'slug' => 'home',
])

@php
    $meta = \App\Support\PageContent::get($slug, 'meta', []);

    $pageTitle = $title ?? ($meta['title'] ?? config('frith.company.name'));
    $pageDescription = $description ?? ($meta['description'] ?? '');

    $shareImage = \App\Support\BrandAsset::url('brand/img/frith-hero-hillside.png');
    $shareImageAlt = \App\Support\PageContent::get($slug, 'hero.image_alt', '');
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

    @if ($noindex || ! \App\Support\SearchVisibility::indexable())
        <meta name="robots" content="noindex, nofollow">
    @else
        <meta name="robots" content="index, follow, max-image-preview:large, max-snippet:-1">
    @endif

    <meta name="author" content="{{ config('frith.company.name') }}">
    <meta name="theme-color" content="#F8F4EE">
    <meta name="color-scheme" content="light">
    <meta name="format-detection" content="telephone=no">
    <meta name="referrer" content="strict-origin-when-cross-origin">

    {{-- Open Graph --}}
    <meta property="og:type" content="website">
    <meta property="og:site_name" content="Frith">
    <meta property="og:title" content="{{ $pageTitle }}">
    <meta property="og:description" content="{{ $pageDescription }}">
    <meta property="og:url" content="{{ url()->current() }}">
    <meta property="og:locale" content="en_GB">
    <meta property="og:image" content="{{ $shareImage }}">
    <meta property="og:image:width" content="519">
    <meta property="og:image:height" content="340">
    <meta property="og:image:type" content="image/png">
    <meta property="og:image:alt" content="{{ $shareImageAlt }}">

    {{-- No @site or @creator until those accounts exist — claiming handles
         that do not is worse than omitting them. --}}
    <meta name="twitter:card" content="summary_large_image">
    <meta name="twitter:title" content="{{ $pageTitle }}">
    <meta name="twitter:description" content="{{ $pageDescription }}">
    <meta name="twitter:image" content="{{ $shareImage }}">
    <meta name="twitter:image:alt" content="{{ $shareImageAlt }}">

    {{-- Both faces are render-blocking on first paint, so they are asked for
         before the stylesheet that will need them. --}}
    <link rel="preload" href="{{ \App\Support\BrandAsset::url('fonts/livvic-400-latin.woff2') }}" as="font" type="font/woff2" crossorigin>
    <link rel="preload" href="{{ \App\Support\BrandAsset::url('fonts/livvic-600-latin.woff2') }}" as="font" type="font/woff2" crossorigin>

    <link rel="icon" href="{{ \App\Support\BrandAsset::url('brand/logo/frith-icon.svg') }}" type="image/svg+xml">
    <link rel="icon" href="{{ \App\Support\BrandAsset::url('brand/icon/frith-icon-32.png') }}" sizes="32x32" type="image/png">
    <link rel="icon" href="{{ \App\Support\BrandAsset::url('brand/icon/frith-icon-16.png') }}" sizes="16x16" type="image/png">
    <link rel="apple-touch-icon" href="{{ \App\Support\BrandAsset::url('brand/icon/frith-icon-180.png') }}" sizes="180x180">
    <link rel="manifest" href="{{ route('manifest') }}">

    @if (! $noindex && \App\Support\SearchVisibility::indexable())
        <script type="application/ld+json">{!! json_encode(\App\Support\StructuredData::home(), JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) !!}</script>
    @endif

    @vite('resources/scss/main.scss')

    {{ $head ?? '' }}
</head>
<body>

    <a class="skip-link" href="#main">Skip to content</a>

    <x-site.header />

    <main id="main">
        {{ $slot }}
    </main>

    <x-site.footer />

</body>
</html>
