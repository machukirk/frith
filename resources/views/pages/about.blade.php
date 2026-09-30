@php
    $c = \App\Support\PageContent::for('about');
@endphp

<x-layouts.site :title="$c['meta']['title']" :description="$c['meta']['description']" slug="about">

    {{-- The moment most parents recognise ---------------------------------- --}}
    <header class="page-head">
        <div class="page-head__inner">
            <p class="eyebrow">{{ $c['page_head']['eyebrow'] }}</p>

            <h1 class="heading page-head__title">{{ $c['page_head']['title'] }}</h1>

            {{-- The standfirst keeps its own size inside .prose; the paragraph
                 under it only needs the gap .prose gives its children. --}}
            <div class="prose">
                <p class="page-head__standfirst">{{ $c['page_head']['standfirst'] }}</p>
                <p>{{ $c['page_head']['body'] }}</p>
            </div>
        </div>
    </header>

    {{-- Mission and vision ------------------------------------------------- --}}
    <section class="band band--inverse">
        <div class="wrap">
            <div class="split split--wide">
                <div class="section-head section-head--on-inverse">
                    <p class="eyebrow eyebrow--on-inverse">{{ $c['purpose']['mission']['eyebrow'] }}</p>
                    <h2 class="heading heading--section section-head__title">{{ $c['purpose']['mission']['title'] }}</h2>
                </div>

                <div class="section-head section-head--on-inverse">
                    <p class="eyebrow eyebrow--on-inverse">{{ $c['purpose']['vision']['eyebrow'] }}</p>
                    <h2 class="heading heading--section section-head__title">{{ $c['purpose']['vision']['title'] }}</h2>
                    <p class="section-head__standfirst">{{ $c['purpose']['vision']['body'] }}</p>
                </div>
            </div>
        </div>
    </section>

    {{-- What we believe ---------------------------------------------------- --}}
    <section class="band band--page">
        <div class="wrap">
            <div class="section-head">
                <p class="eyebrow">{{ $c['values']['eyebrow'] }}</p>
                <h2 class="heading heading--section section-head__title">{{ $c['values']['title'] }}</h2>
                <p class="section-head__standfirst">{{ $c['values']['standfirst'] }}</p>
            </div>

            <ul class="split">
                @foreach ($c['values']['items'] as $value)
                    <li class="panel">
                        <p class="panel__title">{{ $value['title'] }}</p>
                        <p class="panel__body">{{ $value['body'] }}</p>
                    </li>
                @endforeach
            </ul>
        </div>
    </section>

    {{-- How we pay for it -------------------------------------------------- --}}
    <section class="band band--alt">
        <div class="wrap">
            <div class="split split--wide split--lead">
                <div>
                    <div class="section-head">
                        <p class="eyebrow">{{ $c['funding']['eyebrow'] }}</p>
                        <h2 class="heading heading--section section-head__title">{{ $c['funding']['title'] }}</h2>
                    </div>

                    <div class="prose">
                        @foreach ($c['funding']['body'] as $paragraph)
                            <p>{{ $paragraph }}</p>
                        @endforeach
                    </div>
                </div>

                <div class="panel">
                    <p class="panel__label">{{ $c['funding']['timeline']['label'] }}</p>

                    {{-- Title case, not the eyebrow: the eyebrow would shout
                         AUTUMN 2026 at somebody reading about the roadmap. --}}
                    <dl class="timeline">
                        @foreach ($c['funding']['timeline']['items'] as $stage)
                            <div class="timeline__item">
                                <dt class="timeline__term">{{ $stage['term'] }}</dt>
                                <dd class="timeline__body">{{ $stage['body'] }}</dd>
                            </div>
                        @endforeach
                    </dl>

                    <p class="doc-meta">{{ $c['funding']['timeline']['note'] }}</p>
                </div>
            </div>
        </div>
    </section>

</x-layouts.site>
