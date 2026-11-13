{{--
    Something broke at our end.

    Deliberately plain, and deliberately careful about what it reads. This page
    renders when something has already gone wrong, so the less it depends on,
    the less likely it is to fail as well.

    The design draws the full public header and footer here, the same as on
    every other page. They are inlined rather than rendered through
    <x-site.header> and <x-site.footer>, because both of those components reach
    for the things most likely to be what has just broken:

      - the header's phone menu asks auth('founder') who is signed in, which is
        a session read and a database row;
      - the footer calls PageContent::for('footer'), which is a cache read over
        a database row.

    Everything below comes out of config/ instead. Config is a file read, and
    it is already in memory by the time an exception handler runs. The links
    are the same config arrays the real header and footer read, so they cannot
    drift and tests/Feature/FooterLinksTest.php still covers them.

    The one real cost: the four lines of footer copy are the config floor
    (config/content/footer.php), not the stored page. If somebody edits the
    safeguarding promise in the admin panel, this page keeps the shipped
    wording until the next deploy. That is the price of not touching the
    database on the one page whose job is to work when the database does not.
--}}
@php
    $company = config('frith.company');

    // The floor, not PageContent::for('footer'). See the note above.
    $footer = config('frith-content.footer');

    // config('frith-nav.primary') read directly, not SiteNavigation::primary():
    // that method asks the auth guard whether a family is signed in so it can
    // pick a list, and asking the guard anything means a session and a database
    // query. Signed out is the right answer on this page in any case.
    $primary = collect(config('frith-nav.primary', []))
        ->map(fn (array $item) => [
            'label' => $item['label'],
            'url' => \App\Support\SiteNavigation::url($item),
        ]);
@endphp
<!DOCTYPE html>
<html lang="en-GB">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex, nofollow">
    <title>Something has gone wrong at our end · Frith</title>
    @vite('resources/scss/main.scss')
</head>
<body>

    <a class="skip-link" href="#main">Skip to content</a>

    {{-- --static because there is no phone menu to hand off to below md.
         See the matching block in resources/scss/components/_outcome.scss. --}}
    <header class="site-header site-header--static">
        <div class="site-header__inner">
            <a class="site-header__home" href="{{ \App\Support\SiteNavigation::url('home') }}">
                <img class="site-header__logo"
                     src="{{ \App\Support\BrandAsset::url('brand/logo/frith-logo-horizontal-fullcolour.svg') }}"
                     alt="Frith home" width="1152" height="464">
            </a>

            <nav class="site-header__nav" aria-label="Primary">
                @foreach ($primary as $item)
                    <a class="site-header__link" href="{{ $item['url'] }}">{{ $item['label'] }}</a>
                @endforeach

                <a class="site-header__link site-header__link--strong" href="{{ \App\Support\SiteNavigation::url('login') }}">Log in</a>
                <a class="button button--primary button--small" href="{{ \App\Support\SiteNavigation::url('register.start') }}">Register</a>
            </nav>
        </div>
    </header>

    <main id="main">
        <section class="wrap outcome outcome--start">
            <h1 class="heading outcome__title">Something has gone wrong at our end</h1>

            <p class="outcome__body">
                Nothing you did. We have been told about it, and someone is looking.
            </p>

            {{-- --alert, not the base teal: the design draws this one box in
                 the coral set, because it is the only reassurance on the site
                 that answers a failure. The modifier already ships; this
                 template was the half of that fix that never landed. --}}
            <div class="note note--alert note--standalone outcome__note">
                If you were part way through writing something, we have kept it. Go
                back and it will still be there.
            </div>

            <div class="outcome__actions">
                <a class="button button--primary" href="{{ \App\Support\SiteNavigation::url('home') }}">Try again</a>
            </div>
        </section>
    </main>

    <footer class="site-footer">
        <div class="site-footer__inner">
            <div>
                <img class="site-footer__logo"
                     src="{{ \App\Support\BrandAsset::url('brand/logo/frith-logo-horizontal-reversed.svg') }}"
                     alt="Frith" width="1152" height="464">

                <p class="site-footer__tagline">{{ $footer['tagline'] }}</p>
                <p class="site-footer__blurb">{{ $footer['blurb'] }}</p>
                <p class="site-footer__contact">
                    <a href="mailto:{{ $company['contact_email'] }}">{{ $company['contact_email'] }}</a>
                </p>
            </div>

            {{-- SiteNavigation::footer() is safe here where primary() is not:
                 it reads config('frith-nav.footer') and the route table, and it
                 never asks who is signed in. Worth the call rather than a
                 hard-coded list, because the links then stay one source of
                 truth. A copy of them taken from the design would have put the
                 dead "Blog" entry back on this page alone. --}}
            @foreach (\App\Support\SiteNavigation::footer() as $group)
                <nav aria-label="{{ $group['title'] }}">
                    <p class="site-footer__group-title">{{ $group['title'] }}</p>
                    <div class="site-footer__links">
                        @foreach ($group['links'] as $link)
                            <a class="site-footer__link" href="{{ $link['url'] }}">{{ $link['label'] }}</a>
                        @endforeach
                    </div>
                </nav>
            @endforeach
        </div>

        <div class="site-footer__base">
            <p>&copy; {{ now()->year }} {{ $company['name'] }} &middot; {{ $footer['registration'] }}</p>
            <p>{{ $footer['safeguarding'] }}</p>
        </div>
    </footer>

</body>
</html>
