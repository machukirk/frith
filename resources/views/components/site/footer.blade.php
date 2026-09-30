@php
    $groups = \App\Support\SiteNavigation::footer();
    $company = config('frith.company');
@endphp

<footer class="site-footer">
    <div class="site-footer__inner">
        <div>
            <img class="site-footer__logo"
                 src="{{ \App\Support\BrandAsset::url('brand/logo/frith-logo-horizontal-reversed.svg') }}"
                 alt="Frith" width="1152" height="464">

            <p class="site-footer__tagline">{{ config('frith-content.footer.tagline') }}</p>
            <p class="site-footer__blurb">{{ config('frith-content.footer.blurb') }}</p>
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
        <p>&copy; {{ now()->year }} {{ $company['name'] }} &middot; {{ config('frith-content.footer.registration') }}</p>
        <p>{{ config('frith-content.footer.safeguarding') }}</p>
    </div>
</footer>
