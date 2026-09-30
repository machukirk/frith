@props(['title' => null, 'description' => null])

{{-- A logo and the form, and nothing to click away to. Somebody here is
     trying to get in, not browse. --}}
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

    {{-- The full footer, which the hi-fi does give this screen. Only the
         header's navigation goes: somebody who cannot get in still needs the
         help centre and a way to reach a person. --}}
    <x-site.footer />
</body>
</html>
