@php
    $c = \App\Support\PageContent::for('community-guidelines');
@endphp

<x-layouts.site :title="$c['meta']['title']" :description="$c['meta']['description']" slug="community-guidelines">

    <div class="policy">
        <div class="policy__inner">

            <x-site.policy-nav current="community-guidelines" />

            {{-- One column of prose from the eyebrow down, because this page is
                 read end to end rather than scanned. --}}
            <div class="prose">
                <p class="eyebrow">{{ $c['head']['eyebrow'] }}</p>
                <h1 class="heading page-head__title">{{ $c['head']['title'] }}</h1>
                <p class="doc-meta">{{ $c['head']['updated'] }}</p>

                <div class="note note--panel">
                    <span class="note__label">{{ $c['summary']['label'] }}</span>
                    <p>{{ $c['summary']['body'] }}</p>
                </div>

                <p>{{ $c['intro'] }}</p>

                {{-- What we expect ------------------------------------------ --}}
                <h2>{{ $c['expect']['title'] }}</h2>

                <ul>
                    @foreach ($c['expect']['items'] as $item)
                        <li><strong>{{ $item['lead'] }}</strong> {{ $item['body'] }}</li>
                    @endforeach
                </ul>

                {{-- What is not allowed -------------------------------------- --}}
                <h2>{{ $c['not_allowed']['title'] }}</h2>

                <p>{{ $c['not_allowed']['standfirst'] }}</p>

                <ul>
                    @foreach ($c['not_allowed']['items'] as $item)
                        <li>{{ $item }}</li>
                    @endforeach
                </ul>

                {{-- What happens when you report someone --------------------- --}}
                <h2>{{ $c['reporting']['title'] }}</h2>

                @foreach ($c['reporting']['stages'] as $stage)
                    <div class="link-cards__item">
                        <p class="eyebrow">{{ $stage['label'] }}</p>
                        <p>{{ $stage['body'] }}</p>
                    </div>
                @endforeach

                {{-- Blocking ------------------------------------------------- --}}
                <h2>{{ $c['blocking']['title'] }}</h2>

                <p>{{ $c['blocking']['body'] }}</p>

                {{-- If we get it wrong --------------------------------------- --}}
                <h2>{{ $c['appeals']['title'] }}</h2>

                <p>
                    {!! str_replace(
                        ':email',
                        '<a href="mailto:'.e(config('frith.company.contact_email')).'">'.e(config('frith.company.contact_email')).'</a>',
                        e($c['appeals']['body']),
                    ) !!}
                </p>
            </div>

        </div>
    </div>

</x-layouts.site>
