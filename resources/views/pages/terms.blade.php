@php
    $c = \App\Support\PageContent::for('terms');

    // Generated from this page's own row, never typed into the copy.
    // See App\Support\PolicyPages::lastUpdated for why.
    $updated = \App\Support\PolicyPages::lastUpdated('terms');

    $email = config('frith.company.contact_email');

    // ":email" in the copy becomes a real mailto link. The sentence is escaped
    // first, so the anchor is the only markup that ever reaches the page.
    $withEmail = fn (string $copy) => str_replace(
        ':email',
        '<a href="mailto:'.e($email).'">'.e($email).'</a>',
        e($copy),
    );
@endphp

<x-layouts.site :title="$c['meta']['title']" :description="$c['meta']['description']" slug="terms">

    <div class="policy">
        <div class="policy__inner">

            <x-site.policy-nav current="terms" />

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

                    {{-- The terms themselves --------------------------------- --}}
                    @foreach ($c['sections'] as $section)
                        <h2>{{ $section['title'] }}</h2>

                        @foreach ($section['paragraphs'] ?? [] as $paragraph)
                            <p>{{ $paragraph }}</p>
                        @endforeach

                        @if (! empty($section['items']))
                            <ul>
                                @foreach ($section['items'] as $item)
                                    <li>{{ $item }}</li>
                                @endforeach
                            </ul>
                        @endif

                        {{-- "What we do not promise" is a panel, not prose. --}}
                        @if (! empty($section['callout']))
                            <p class="note note--panel">{{ $section['callout'] }}</p>
                        @endif
                    @endforeach

                    <p class="section-note">{!! $withEmail($c['closing']) !!}</p>

                </div>
            </article>

        </div>
    </div>

</x-layouts.site>
