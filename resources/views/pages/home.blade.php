@php
    $c = \App\Support\PageContent::for('home');
@endphp

<x-layouts.site :title="$c['meta']['title']" :description="$c['meta']['description']">

    {{-- Hero ------------------------------------------------------------- --}}
    <section class="hero">
        <div class="hero__inner">
            <div>
                <p class="eyebrow">{{ $c['hero']['eyebrow'] }}</p>

                <h1 class="heading heading--hero hero__headline">
                    @foreach ($c['hero']['headline'] as $line)
                        {{ $line }}@if (! $loop->last)<br>@else<span class="hero__stop">.</span>@endif
                    @endforeach
                </h1>

                <p class="lead hero__standfirst">{{ $c['hero']['standfirst'] }}</p>

                <div class="hero__actions">
                    <a class="button button--primary" href="{{ route('register.start') }}">{{ $c['hero']['primary_cta'] }}</a>
                    <a class="action-link" href="{{ \App\Support\SiteNavigation::url('how-it-works') }}">
                        {{ $c['hero']['secondary_cta'] }} <span aria-hidden="true">&rarr;</span>
                    </a>
                </div>

                <p class="hero__note">{{ $c['hero']['note'] }}</p>
            </div>

            <figure class="hero__figure">
                <div class="hero__plate">
                    <img class="hero__image"
                         src="{{ \App\Support\BrandAsset::url('brand/img/frith-hero-hillside.png') }}"
                         alt="{{ $c['hero']['image_alt'] }}" width="922" height="604">
                </div>
                {{-- Decorative, and over the photograph rather than beside it. --}}
                <img class="hero__swoosh" src="{{ \App\Support\BrandAsset::url('brand/img/frith-swoosh-hero.svg') }}"
                     alt="" aria-hidden="true">
                <span class="hero__dot" aria-hidden="true"></span>
            </figure>
        </div>
    </section>

    {{-- The three reasons ------------------------------------------------ --}}
    <section class="band band--inverse band--reasons">
        <div class="wrap">
            <ul class="pillars">
                @foreach ($c['pillars'] as $pillar)
                    <li class="pillars__item">
                        <x-site.icon :name="$pillar['icon']" class="pillars__icon" />
                        <p class="pillars__title">{{ $pillar['title'] }}</p>
                        <p class="pillars__body">{{ $pillar['body'] }}</p>
                    </li>
                @endforeach
            </ul>
        </div>
    </section>

    {{-- How it works ----------------------------------------------------- --}}
    <section class="band band--alt" id="how-it-works">
        <div class="wrap">
            <div class="section-head">
                <p class="eyebrow">{{ $c['how_it_works']['eyebrow'] }}</p>
                <h2 class="heading heading--section section-head__title">{{ $c['how_it_works']['title'] }}</h2>
                <p class="section-head__standfirst">{{ $c['how_it_works']['standfirst'] }}</p>
            </div>

            <ol class="steps">
                @foreach ($c['how_it_works']['steps'] as $step)
                    <li class="steps__item">
                        <span class="steps__number" aria-hidden="true">{{ $loop->iteration }}</span>
                        <p class="steps__title">{{ $step['title'] }}</p>
                        <p class="steps__body">{{ $step['body'] }}</p>
                    </li>
                @endforeach
            </ol>

            <p class="section-note">{{ $c['how_it_works']['note'] }}</p>
        </div>
    </section>

    {{-- Journeys --------------------------------------------------------- --}}
    <section class="band band--page">
        <div class="wrap">
            <div class="section-head">
                <p class="eyebrow">{{ $c['journeys']['eyebrow'] }}</p>
                <h2 class="heading heading--section section-head__title">{{ $c['journeys']['title'] }}</h2>
                <p class="section-head__standfirst">{{ $c['journeys']['standfirst'] }}</p>
            </div>

            {{-- Live, not copy. The titles, the counts and how many there are
                 all come from the journeys table, so a Journey renamed or
                 archived in the admin changes the front page with it. --}}
            @php $journeys = \App\Support\JourneyBoard::forHome(); @endphp

            <ul class="journeys">
                @foreach ($journeys as $journey)
                    @php
                        // A dot each for the families in the Journey, up to six,
                        // and a count for whatever is left over.
                        $dots = min(6, $journey['families']);
                        $more = $journey['families'] - $dots;
                    @endphp
                    <li @class(['journeys__cell', 'journeys__cell--featured' => $loop->first])>
                        <a @class(['journeys__item', 'journeys__item--featured' => $loop->first]) href="{{ $journey['url'] }}">
                            <span class="journeys__title">{{ $journey['title'] }}</span>
                            <span class="journeys__meta">
                                <span class="journeys__faces" aria-hidden="true">
                                    @for ($i = 0; $i < $dots; $i++)
                                        <span class="journeys__face"></span>
                                    @endfor
                                    @if ($more > 0)
                                        <span class="journeys__faces-more">+{{ $more }}</span>
                                    @endif
                                </span>
                                {{ trans_choice(':count family|:count families', $journey['families'], ['count' => $journey['families']]) }}
                            </span>
                        </a>
                    </li>
                @endforeach
            </ul>

            <div class="journeys-footer">
                <p class="section-note">
                    {{ str_replace(':count', \App\Support\Words::count($journeys->count()), $c['journeys']['note']) }}
                </p>
                <a class="action-link" href="{{ \App\Support\SiteNavigation::url('journeys') }}">
                    {{ $c['journeys']['link'] }} <span aria-hidden="true">&rarr;</span>
                </a>
            </div>
        </div>
    </section>

    {{-- What others see -------------------------------------------------- --}}
    <section class="band band--inverse">
        <div class="wrap">
            <div class="section-head section-head--tight section-head--on-inverse">
                <p class="eyebrow eyebrow--on-inverse">{{ $c['visibility']['eyebrow'] }}</p>
                <h2 class="heading heading--section section-head__title">{{ $c['visibility']['title'] }}</h2>
                <p class="section-head__standfirst">{{ $c['visibility']['standfirst'] }}</p>
            </div>

            <div class="visibility">
                @foreach (['shown', 'hidden'] as $kind)
                    @php $panel = $c['visibility'][$kind]; @endphp
                    <div class="visibility__panel">
                        <p class="visibility__title visibility__title--{{ $kind }}">{{ $panel['title'] }}</p>
                        <p class="visibility__standfirst">{{ $panel['standfirst'] }}</p>

                        <ul class="visibility__list">
                            @foreach ($panel['items'] as $item)
                                <li class="visibility__row">
                                    <span class="visibility__mark visibility__mark--{{ $kind }}" aria-hidden="true">{{ $kind === 'shown' ? '✓' : '✕' }}</span>
                                    <span>
                                        <span class="visibility__label">{{ $item['label'] }}</span>
                                        <span class="visibility__detail">{{ $item['detail'] }}</span>
                                    </span>
                                </li>
                            @endforeach
                        </ul>
                    </div>
                @endforeach
            </div>
        </div>
    </section>

    {{-- Call to action --------------------------------------------------- --}}
    <section class="band band--inverse band--tight cta-band">
        <div class="cta-band__inner">
            <div>
                <p class="cta-band__title">{{ $c['cta']['title'] }}</p>
                <p class="cta-band__body">{{ $c['cta']['body'] }}</p>
            </div>
            <a class="button button--accent" href="{{ route('register.start') }}">{{ $c['cta']['button'] }}</a>
        </div>
    </section>

    {{-- Questions -------------------------------------------------------- --}}
    <section class="band band--page">
        <div class="wrap">
            <div class="section-head">
                <p class="eyebrow">{{ $c['faq']['eyebrow'] }}</p>
                <h2 class="heading heading--section section-head__title">{{ $c['faq']['title'] }}</h2>
            </div>

            <div class="faq">
                @foreach ($c['faq']['items'] as $item)
                    <details class="faq__item" @if ($loop->first) open @endif>
                        <summary class="faq__summary">
                            {{ $item['question'] }}
                            <span class="faq__mark" aria-hidden="true"></span>
                        </summary>
                        <p class="faq__body">{{ $item['answer'] }}</p>
                    </details>
                @endforeach
            </div>

            <p class="faq-footer">
                {!! str_replace(
                    ':email',
                    '<a href="mailto:'.e(config('frith.company.contact_email')).'">'.e(config('frith.company.contact_email')).'</a>',
                    e($c['faq']['footer']),
                ) !!}
            </p>
        </div>
    </section>

</x-layouts.site>
