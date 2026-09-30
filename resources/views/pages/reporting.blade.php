@php
    $c = \App\Support\PageContent::for('reporting');
    $contactEmail = config('frith.company.contact_email');
@endphp

<x-layouts.site :title="$c['meta']['title']" :description="$c['meta']['description']" slug="reporting">

    <div class="policy">
        <div class="policy__inner">

            <x-site.policy-nav current="reporting" />

            {{-- One .prose column holds the whole policy, so the rhythm between a
                 paragraph, a list and a set of cards is the same rhythm. --}}
            <div class="prose">

                {{-- Head ------------------------------------------------------ --}}
                <p class="eyebrow">{{ $c['head']['eyebrow'] }}</p>
                <h1 class="heading page-head__title">{{ $c['head']['title'] }}</h1>
                <p class="link-cards__body">{{ $c['head']['meta'] }}</p>

                {{-- The short version ----------------------------------------- --}}
                <div class="note note--panel">
                    <p class="note__label">{{ $c['summary']['label'] }}</p>
                    <p>{{ $c['summary']['body'] }}</p>
                </div>

                {{-- 999 first, and amber so it reads as urgent ---------------- --}}
                <div class="note note--panel note--urgent">
                    <p><strong>{{ $c['urgent']['lead'] }}</strong> {{ $c['urgent']['body'] }}</p>
                </div>

                {{-- Reporting a family ----------------------------------------- --}}
                <h2>{{ $c['how_to_report']['title'] }}</h2>

                @foreach ($c['how_to_report']['paragraphs'] as $paragraph)
                    <p>{{ $paragraph }}</p>
                @endforeach

                {{-- What we do with it ----------------------------------------- --}}
                <h2>{{ $c['what_we_do']['title'] }}</h2>

                <ul>
                    @foreach ($c['what_we_do']['items'] as $item)
                        <li>{{ $item }}</li>
                    @endforeach
                </ul>

                <p>{{ $c['what_we_do']['note'] }}</p>

                {{-- Complaining about a decision we made ----------------------- --}}
                <h2>{{ $c['complaints']['title'] }}</h2>

                <p>{{ $c['complaints']['standfirst'] }}</p>

                {{-- Divs rather than an <ol>: inside .prose an ordered list is
                     indented and re-spaced as body copy, which is not this. The
                     numbers are left readable because nothing else carries the
                     order. --}}
                <div class="step-list">
                    @foreach ($c['complaints']['steps'] as $step)
                        <div class="step-list__item">
                            <span class="step-list__number">{{ $loop->iteration }}</span>
                            <div>
                                <p class="step-list__title">{{ str_replace(':email', $contactEmail, $step['title']) }}</p>
                                <p class="step-list__body">{{ $step['body'] }}</p>
                            </div>
                        </div>
                    @endforeach
                </div>

                {{-- If you are still not happy -------------------------------- --}}
                <h2>{{ $c['escalation']['title'] }}</h2>

                <p>{{ $c['escalation']['body'] }}</p>

                {{-- Where to write --------------------------------------------- --}}
                <h2>{{ $c['where_to_write']['title'] }}</h2>

                <div class="link-cards link-cards--tight">
                    @foreach ($c['where_to_write']['cards'] as $card)
                        @php $address = str_replace(':email', $contactEmail, $card['email']); @endphp

                        <div class="link-cards__item">
                            <p class="link-cards__title">{{ $card['title'] }}</p>

                            <div class="link-cards__links">
                                <a class="link-cards__link" href="mailto:{{ $address }}">{{ $address }}</a>
                            </div>

                            <p class="link-cards__body">{{ $card['response'] }}</p>
                        </div>
                    @endforeach
                </div>

            </div>
        </div>
    </div>

</x-layouts.site>
