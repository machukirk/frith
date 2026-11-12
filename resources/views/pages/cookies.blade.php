@php
    $c = \App\Support\PageContent::for('cookies');

    // Generated from this page's own row, never typed into the copy.
    // See App\Support\PolicyPages::lastUpdated for why.
    $updated = \App\Support\PolicyPages::lastUpdated('cookies');
@endphp

<x-layouts.site :title="$c['meta']['title']" :description="$c['meta']['description']" slug="cookies">

    {{-- The policy shell, the same one the other legal pages use ---------- --}}
    <div class="policy">
        <div class="policy__inner">

            {{-- No current="cookies" passed on purpose. App\Support\PolicyPages
                 does not list this page, so nothing here would be marked
                 current and the sidebar reads as "the other policies". Adding
                 'cookies' to PolicyPages::PAGES is the right fix and belongs
                 with that file; this view needs no change when it lands. --}}
            <x-site.policy-nav current="cookies" />

            <article>
                <p class="eyebrow">{{ $c['head']['eyebrow'] }}</p>
                <h1 class="heading page-head__title">{{ $c['head']['title'] }}</h1>
                @if ($updated)
                    <p class="doc-meta">{{ $updated }}</p>
                @endif

                <div class="prose">

                    {{-- The short version ------------------------------------ --}}
                    <div class="note note--panel">
                        <span class="note__label">{{ $c['short_version']['label'] }}</span>
                        <p>{{ $c['short_version']['body'] }}</p>
                    </div>

                    <p>{{ $c['intro'] }}</p>

                    {{-- Every cookie we set ---------------------------------- --}}
                    <h2>{{ $c['cookies']['title'] }}</h2>

                    {{-- Scrolls on a phone rather than squashing three columns
                         into 375px. tabindex so the scroll is reachable from a
                         keyboard, and labelled so it is announced before you
                         arrive in it. Same treatment as the table on the
                         Privacy Policy, for the same reason. --}}
                    <div class="data-table__scroll"
                         role="region"
                         aria-label="{{ $c['cookies']['title'] }}"
                         tabindex="0">
                        <table class="data-table">
                        <thead>
                            <tr>
                                <th scope="col">{{ $c['cookies']['columns']['name'] }}</th>
                                <th scope="col">{{ $c['cookies']['columns']['what'] }}</th>
                                <th scope="col">{{ $c['cookies']['columns']['how_long'] }}</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($c['cookies']['rows'] as $row)
                                <tr>
                                    <td>{{ $row['name'] }}</td>
                                    <td>{{ $row['what'] }}</td>
                                    <td>{{ $row['how_long'] }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                        </table>
                    </div>

                    {{-- The rest of the page -------------------------------- --}}
                    @foreach ($c['sections'] as $section)
                        <h2>{{ $section['title'] }}</h2>

                        @foreach ($section['paragraphs'] ?? [] as $paragraph)
                            <p>{{ $paragraph }}</p>
                        @endforeach
                    @endforeach

                    <p>
                        {{ $c['more']['body'] }}
                        <a href="{{ route('privacy') }}">{{ $c['more']['link'] }}</a>
                    </p>

                    <p class="doc-meta">
                        {{ $c['footer']['label'] }}
                        <a href="mailto:{{ config('frith.company.contact_email') }}">{{ config('frith.company.contact_email') }}</a>
                    </p>

                </div>
            </article>

        </div>
    </div>

</x-layouts.site>
