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

            {{-- Why there is no "buy" here.

                 This button used to read "Explore Frith+" and point at
                 SiteNavigation::url('frith-plus.choose'). That route has never
                 existed, so the url() helper did what it is designed to do and
                 returned '#': the single call to action on the page that sells
                 the paid plan was a dead anchor.

                 Payment screens are deferred by agreed scope, so the fix is
                 this button, not the screens behind it. Registering is a real
                 thing a visitor can do today, and the note says plainly that
                 there is nothing to buy yet.

                 Do not put a Frith+ purchase button back until there is a
                 route with a screen on the end of it. --}}
            <div class="note note--panel note--standalone">
                <span class="note__label">{{ $c['cta']['label'] }}</span>
                <p>{{ $c['cta']['body'] }}</p>
            </div>

            <div class="action-row action-row--bare">
                <a class="button button--accent" href="{{ route('register.start') }}">{{ $c['cta']['button'] }}</a>
            </div>
        </div>
    </section>

</x-layouts.site>
