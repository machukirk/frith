@php
    $c = \App\Support\PageContent::for('meet-up-safety');
@endphp

<x-layouts.site :title="$c['meta']['title']" :description="$c['meta']['description']" slug="meet-up-safety">

    <section class="policy">
        <div class="policy__inner">

            <x-site.policy-nav current="meet-up-safety" />

            <article>
                <div class="section-head">
                    <p class="eyebrow">{{ $c['head']['eyebrow'] }}</p>
                    <h1 class="heading page-head__title">{{ $c['head']['title'] }}</h1>
                    <p class="doc-meta">{{ $c['head']['updated'] }}</p>
                </div>

                <div class="prose">
                    {{-- The short version, for anyone who reads no further --}}
                    <div class="note note--panel">
                        <p class="note__label">{{ $c['short_version']['label'] }}</p>
                        <p>{{ $c['short_version']['body'] }}</p>
                    </div>

                    <p>{{ $c['intro'] }}</p>

                    <h2>{{ $c['before_you_go']['title'] }}</h2>

                    <ul>
                        @foreach ($c['before_you_go']['items'] as $item)
                            <li>{{ $item }}</li>
                        @endforeach
                    </ul>

                    <h2>{{ $c['not_right']['title'] }}</h2>

                    <p>
                        {!! str_replace(
                            ':action',
                            '<strong>'.e($c['not_right']['action']).'</strong>',
                            e($c['not_right']['body']),
                        ) !!}
                    </p>

                    {{-- Amber, not teal. This is the one urgent line on a page
                         of calm ones, and it has to look like it. --}}
                    <div class="note note--panel note--urgent">
                        <p>
                            {!! str_replace(
                                ':number',
                                '<strong>'.e($c['emergency']['number']).'</strong>',
                                e($c['emergency']['body']),
                            ) !!}
                        </p>
                    </div>

                    <h2>{{ $c['checks']['title'] }}</h2>

                    <p>{{ $c['checks']['body'] }}</p>
                </div>

                {{-- Outside .prose on purpose: `.prose a` would win over
                     `.button--primary` and paint the label the same green as
                     the button behind it. --}}
                <div class="action-row">
                    <a class="button button--primary button--small" href="{{ \App\Support\SiteNavigation::url('reporting') }}">
                        {{ $c['actions']['report'] }}
                    </a>
                    <a class="button button--quiet button--small" href="{{ \App\Support\SiteNavigation::url('community-guidelines') }}">
                        {{ $c['actions']['guidelines'] }}
                    </a>
                </div>
            </article>

        </div>
    </section>

</x-layouts.site>
