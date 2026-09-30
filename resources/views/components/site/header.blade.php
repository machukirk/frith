@php
    $nav = \App\Support\SiteNavigation::primary();
    $founder = auth('founder')->user();
@endphp

{{--
    The phone menu is a native <details>, not a scripted panel. It opens with no
    JavaScript, a keyboard already knows how to work it, and a screen reader
    announces it as a disclosure without being told to.
--}}
<header class="site-header">
    <details class="site-header__disclosure">
        <summary class="site-header__bar">
            <span class="site-header__home-slot">
                <img class="site-header__logo"
                     src="{{ \App\Support\BrandAsset::url('brand/logo/frith-logo-horizontal-fullcolour.svg') }}"
                     alt="Frith" width="1152" height="464">
            </span>

            <span class="site-header__toggle" aria-hidden="true">
                <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                     stroke-width="2" stroke-linecap="round">
                    <path class="site-header__toggle-open" d="M3 6h18M3 12h18M3 18h18"/>
                    <path class="site-header__toggle-close" d="M6 6l12 12M18 6L6 18"/>
                </svg>
            </span>
            <span class="visually-hidden">Menu</span>
        </summary>

        <div class="site-header__panel wrap">
            @foreach ($nav as $item)
                <a class="site-header__panel-link" href="{{ $item['url'] }}">{{ $item['label'] }}</a>
            @endforeach

            @if ($founder)
                <span class="site-header__panel-link site-header__panel-link--name">{{ $founder->displayName() }}</span>
                <form action="{{ route('logout') }}" method="POST">
                    @csrf
                    <button class="button button--quiet button--block" type="submit">Log out</button>
                </form>
            @else
                <a class="site-header__panel-link" href="{{ route('login') }}">Log in</a>
                <a class="button button--primary button--block" href="{{ route('register.start') }}">Register</a>
            @endif
        </div>
    </details>

    {{-- The same links again for anything wide enough to show them outright.
         Hidden from assistive tech on a phone so the menu is not read twice. --}}
    <div class="site-header__inner">
        <a class="site-header__home" href="{{ route('home') }}">
            <img class="site-header__logo"
                 src="{{ \App\Support\BrandAsset::url('brand/logo/frith-logo-horizontal-fullcolour.svg') }}"
                 alt="Frith — home" width="1152" height="464">
        </a>

        <nav class="site-header__nav" aria-label="Primary">
            @foreach ($nav as $item)
                <a class="site-header__link" href="{{ $item['url'] }}">{{ $item['label'] }}</a>
            @endforeach

            @if ($founder)
                <span class="site-header__link">{{ $founder->displayName() }}</span>
                <form action="{{ route('logout') }}" method="POST">
                    @csrf
                    <button class="button button--quiet button--small" type="submit">Log out</button>
                </form>
            @else
                <a class="site-header__link site-header__link--strong" href="{{ route('login') }}">Log in</a>
                <a class="button button--primary button--small" href="{{ route('register.start') }}">Register</a>
            @endif
        </nav>
    </div>
</header>
