@php
    $c = \App\Support\PageContent::for('privacy');
    $dataEmail = $c['data_email'];
@endphp

<x-layouts.site :title="$c['meta']['title']" :description="$c['meta']['description']" slug="privacy">

    {{-- The policy shell: the other policies beside the prose ------------- --}}
    <div class="policy">
        <div class="policy__inner">

            <x-site.policy-nav current="privacy" />

            <article>
                <p class="eyebrow">{{ $c['head']['eyebrow'] }}</p>
                <h1 class="heading page-head__title">{{ $c['head']['title'] }}</h1>
                <p class="doc-meta">{{ $c['head']['doc_meta'] }}</p>

                <div class="prose">

                    {{-- The short version ------------------------------------ --}}
                    <div class="note note--panel">
                        <span class="note__label">{{ $c['short_version']['label'] }}</span>
                        <p>{{ $c['short_version']['body'] }}</p>
                    </div>

                    <p>{{ $c['intro'] }}</p>

                    {{-- What we hold, and who sees it ------------------------ --}}
                    <h2>{{ $c['holdings']['title'] }}</h2>

                    <table>
                        <thead>
                            <tr>
                                <th scope="col">{{ $c['holdings']['columns']['what'] }}</th>
                                <th scope="col">{{ $c['holdings']['columns']['why'] }}</th>
                                <th scope="col">{{ $c['holdings']['columns']['who'] }}</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($c['holdings']['rows'] as $row)
                                <tr>
                                    <td>{{ $row['what'] }}</td>
                                    <td>{{ $row['why'] }}</td>
                                    <td>{{ $row['who'] }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>

                    {{-- The rest of the policy ------------------------------- --}}
                    @foreach ($c['sections'] as $section)
                        <h2>{{ $section['title'] }}</h2>

                        @foreach ($section['paragraphs'] ?? [] as $paragraph)
                            <p>{{ str_replace(':email', $dataEmail, $paragraph) }}</p>
                        @endforeach

                        @if (! empty($section['items']))
                            <ul>
                                @foreach ($section['items'] as $item)
                                    <li>{{ $item }}</li>
                                @endforeach
                            </ul>
                        @endif
                    @endforeach

                    <p class="doc-meta">
                        {{ $c['footer']['label'] }}
                        <a href="mailto:{{ $dataEmail }}">{{ $dataEmail }}</a>
                    </p>

                </div>
            </article>

        </div>
    </div>

</x-layouts.site>
