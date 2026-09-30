@php
    $c = \App\Support\PageContent::for('frith-plus');
@endphp

<x-layouts.site :title="$c['meta']['title']" :description="$c['meta']['description']" slug="frith-plus">

    {{-- Frith+ ------------------------------------------------------------ --}}
    <section class="band band--inverse">
        <div class="wrap">
            <div class="section-head section-head--on-inverse">
                {{-- The page's own heading, so it is the h1 even though the
                     hi-fi draws it inside the app shell as an h2. --}}
                <h1 class="heading heading--section section-head__title">{{ $c['intro']['title'] }}</h1>
                <p class="section-head__standfirst">{{ $c['intro']['standfirst'] }}</p>
            </div>

            <p class="eyebrow eyebrow--on-inverse">{{ $c['benefits']['label'] }}</p>

            <ul class="benefit-list">
                @foreach ($c['benefits']['items'] as $item)
                    <li class="benefit-list__item">
                        <span class="benefit-list__title">{{ $item['title'] }}</span>
                        <span class="benefit-list__detail">{{ $item['detail'] }}</span>
                    </li>
                @endforeach
            </ul>

            <div class="action-row action-row--bare">
                <a class="button button--accent" href="{{ \App\Support\SiteNavigation::url('frith-plus.choose') }}">{{ $c['cta']['button'] }}</a>
            </div>
        </div>
    </section>

</x-layouts.site>
