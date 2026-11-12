@php
    $groups = \App\Support\SiteNavigation::footer();
    $company = config('frith.company');

    // PageContent, not config(). The config file is only the floor, and a
    // config() read never sees the stored row, so these four lines used to be
    // the one piece of copy on the site that stayed developer-only even after
    // somebody added a pages row for it. One of them is a promise with a
    // number in it, the 24-hour safeguarding reply, and that is exactly the
    // kind of sentence somebody needs to be able to change without a deploy.
    $footer = \App\Support\PageContent::for('footer');
@endphp

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

        @foreach ($groups as $group)
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
