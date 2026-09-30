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
    <details class="menu">
        <summary class="menu__bar">
            <span class="menu__brand">
                {{-- Two marks, one shown at a time: the menu's own background
                     is the deep green, so the closed bar's logo cannot stay. --}}
                <img class="menu__logo menu__logo--closed"
                     src="{{ \App\Support\BrandAsset::url('brand/logo/frith-logo-horizontal-fullcolour.svg') }}"
                     alt="Frith" width="1152" height="464">
                <img class="menu__logo menu__logo--open"
                     src="{{ \App\Support\BrandAsset::url('brand/logo/frith-logo-horizontal-reversed.svg') }}"
                     alt="" aria-hidden="true" width="1152" height="464">
            </span>

            <span class="menu__toggle" aria-hidden="true">
                <svg class="menu__toggle-open" width="22" height="22" viewBox="0 0 24 24" fill="none"
                     stroke="currentColor" stroke-width="2" stroke-linecap="round">
                    <path d="M3 6h18M3 12h18M3 18h18"/>
                </svg>
                <span class="menu__close">&#10005;</span>
            </span>
            <span class="visually-hidden">Menu</span>
        </summary>

        <div class="menu__panel @if ($founder) menu__panel--founder @endif">
            @if ($founder)
                <div class="menu__profile">
                    <span class="menu__avatar" aria-hidden="true">{{ Str::upper(Str::substr($founder->displayName(), 0, 1)) }}</span>
                    <span>
                        <span class="menu__name">{{ $founder->displayName() }}</span>
                        @if ($founder->postcode_outcode)
                            <span class="menu__area">{{ $founder->postcode_outcode }} area</span>
                        @endif
                    </span>
                </div>
            @endif

            <nav class="menu__nav" aria-label="Primary">
                @foreach (\App\Support\SiteNavigation::menu((bool) $founder) as $item)
                    <a class="menu__row" href="{{ $item['url'] }}"
                       @if (url()->current() === $item['url']) aria-current="page" @endif>
                        <span>
                            <span class="menu__label">{{ $item['label'] }}</span>
                            @if ($item['meta'])
                                <span class="menu__meta">{{ $item['meta'] }}</span>
                            @endif
                        </span>
                        <span class="menu__chevron" aria-hidden="true">&rsaquo;</span>
                    </a>
                @endforeach
            </nav>

            <div class="menu__foot">
                @unless ($founder)
                    <a class="menu__cta" href="{{ route('register.start') }}">Register</a>
                    <a class="menu__cta menu__cta--quiet" href="{{ route('login') }}">Log in</a>
                @endunless

                <div class="menu__links">
                    @foreach (\App\Support\SiteNavigation::menuFoot((bool) $founder) as $link)
                        <a class="menu__link" href="{{ $link['url'] }}">{{ $link['label'] }}</a>
                    @endforeach

                    @if ($founder)
                        <form action="{{ route('logout') }}" method="POST">
                            @csrf
                            <button class="menu__link menu__link--out" type="submit">Log out</button>
                        </form>
                    @endif
                </div>
            </div>
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
