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
    <div class="menu">
        {{-- A link, not part of the toggle. The whole bar used to be the
             <summary>, so tapping the wordmark opened the menu instead of
             going home. --}}
        <a class="menu__home" href="{{ route('home') }}">
            <img class="menu__logo menu__logo--closed"
                 src="{{ \App\Support\BrandAsset::url('brand/logo/frith-logo-horizontal-fullcolour.svg') }}"
                 alt="Frith home" width="1152" height="464">
            <img class="menu__logo menu__logo--open"
                 src="{{ \App\Support\BrandAsset::url('brand/logo/frith-logo-horizontal-reversed.svg') }}"
                 alt="" aria-hidden="true" width="1152" height="464">
        </a>

        <details class="menu__disclosure">
            <summary class="menu__toggle">
                <svg class="menu__burger" width="26" height="26" viewBox="0 0 24 24" fill="none"
                     stroke="currentColor" stroke-width="2" stroke-linecap="round">
                    <path d="M3 6h18M3 12h18M3 18h18"/>
                </svg>
                <span class="menu__close" aria-hidden="true">&#10005;</span>
                <span class="visually-hidden">Menu</span>
            </summary>

        <div class="menu__panel @if ($founder) menu__panel--founder @endif">
            @if ($founder)
                <div class="menu__profile">
                    <span class="menu__avatar" aria-hidden="true">{{ Str::upper(Str::substr($founder->displayName(), 0, 1)) }}</span>
                    <span>
                        <span class="menu__name">{{ $founder->displayName() }}</span>
                        @if ($founder->area())
                            {{-- The town, not the outcode: it is the same
                                 thing other families are shown about them. --}}
                            <span class="menu__area">{{ $founder->area() }}</span>
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
    </div>

    {{-- The same links again for anything wide enough to show them outright.
         Hidden from assistive tech on a phone so the menu is not read twice. --}}
    <div class="site-header__inner">
        <a class="site-header__home" href="{{ route('home') }}">
            <img class="site-header__logo"
                 src="{{ \App\Support\BrandAsset::url('brand/logo/frith-logo-horizontal-fullcolour.svg') }}"
                 alt="Frith home" width="1152" height="464">
        </a>

        <nav class="site-header__nav" aria-label="Primary">
            @foreach ($nav as $item)
                <a class="site-header__link" href="{{ $item['url'] }}">{{ $item['label'] }}</a>
            @endforeach

            @if ($founder)
                {{-- A native <details>, like the phone menu. There is no
                     JavaScript on this site, so a scripted dropdown would be
                     a menu some people could not open. The trade is that it
                     does not close on an outside click; pressing the avatar
                     again closes it, and a keyboard already knows how.

                     Clicking outside it is what a mouse user expects, and the
                     only way to have that is a script. Weighed and refused:
                     the audience here reads this on a cracked phone at 11pm,
                     and a menu that needs JavaScript to open is worse than
                     one that needs a second press to shut. --}}
                <details class="account-menu">
                    <summary class="account-menu__toggle">
                        <span class="account-menu__avatar" aria-hidden="true">{{ $founder->initial() }}</span>
                        <span class="account-menu__chevron" aria-hidden="true">&#8964;</span>
                        <span class="visually-hidden">{{ $founder->displayName() }}, and your account</span>
                    </summary>

                    <div class="account-menu__panel">
                        <div class="account-menu__who">
                            <span class="account-menu__avatar account-menu__avatar--large" aria-hidden="true">{{ $founder->initial() }}</span>
                            <span class="account-menu__who-text">
                                <span class="account-menu__name">{{ $founder->displayName() }}</span>
                                @if ($founder->area())
                                    <span class="account-menu__area">{{ $founder->area() }}</span>
                                @endif
                            </span>

                            {{-- The designs draw a bell with a coral dot. The
                                 dot would be a count of what you have missed,
                                 which this product says twice over that it
                                 does not do, so the bell is a way to the
                                 settings and the waiting is said in words on
                                 the Requests row below. --}}
                            <a class="account-menu__bell" href="{{ route('account.notifications') }}">
                                <svg viewBox="0 0 24 24" width="16" height="16" fill="none" stroke="currentColor"
                                     stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                                    <path d="M18 15.5V10a6 6 0 1 0-12 0v5.5L4.5 18h15Z"/>
                                    <path d="M10 20.5a2.2 2.2 0 0 0 4 0"/>
                                </svg>
                                <span class="visually-hidden">Which emails you get</span>
                            </a>
                        </div>

                        <nav class="account-menu__nav" aria-label="Your account">
                            @foreach (\App\Support\AccountMenu::rows($founder) as $row)
                                <a class="account-menu__row @if ($row['current']) account-menu__row--current @endif"
                                   href="{{ $row['url'] }}"
                                   @if ($row['current']) aria-current="page" @endif>
                                    <span class="account-menu__label">{{ $row['label'] }}</span>
                                    @if ($row['meta'])
                                        <span class="account-menu__meta">{{ $row['meta'] }}</span>
                                    @endif
                                </a>
                            @endforeach
                        </nav>

                        <div class="account-menu__foot">
                            <a class="account-menu__plus" href="{{ route('frith-plus') }}">Frith+ membership</a>
                            <a class="account-menu__row" href="{{ route('help') }}">
                                <span class="account-menu__label">Help centre</span>
                            </a>

                            <form action="{{ route('logout') }}" method="POST">
                                @csrf
                                <button class="account-menu__out" type="submit">Log out</button>
                            </form>
                        </div>
                    </div>
                </details>
            @else
                <a class="site-header__link site-header__link--strong" href="{{ route('login') }}">Log in</a>
                <a class="button button--primary button--small" href="{{ route('register.start') }}">Register</a>
            @endif
        </nav>
    </div>
</header>
