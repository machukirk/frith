<x-layouts.site title="Journeys — Frith" :noindex="true">

    <section class="wrap page-head">
        <div class="page-head__inner">
            <h1 class="heading page-head__title">Journeys</h1>
            <p class="page-head__standfirst">
                Groups of families navigating the same thing. Joining one makes your
                profile visible to the others in it.
            </p>
        </div>
    </section>

    <section class="wrap journey-list">
        @if (session('status'))
            <p class="journey-list__status" role="status">{{ session('status') }}</p>
        @endif

        <form class="journey-list__filter" method="GET" action="{{ route('journeys') }}">
            <label class="search">
                <span class="visually-hidden">Search Journeys</span>
                <x-site.icon name="search" class="search__icon" />
                <input class="search__input" type="search" name="q" value="{{ $search }}"
                       placeholder='Search Journeys &mdash; try "school" or "sleep"'>
            </label>

            @if ($search !== '')
                <a class="journey-list__all" href="{{ route('journeys') }}">Show all {{ $total }}</a>
            @else
                <p class="journey-list__count">All {{ $total }}</p>
            @endif
        </form>

        @if ($journeys->isEmpty())
            <p class="journey-list__empty">
                Nothing matches &ldquo;{{ $search }}&rdquo;.
                <a href="{{ route('journeys') }}">Show all {{ $total }}</a>.
            </p>
        @endif

        <ul class="journey-list__items">
            @foreach ($journeys as $journey)
                @php
                    $isMember = in_array($journey->id, $joined, true);
                    $others = $journey->members_count - 1;

                    // Said to the person reading it: you are one of these
                    // families, so the count should not pretend otherwise.
                    $who = $isMember
                        ? 'You and '.$others.' '.\Illuminate\Support\Str::plural('other', $others).' are in this journey'
                        : $journey->members_count.' '.\Illuminate\Support\Str::plural('family', $journey->members_count)
                            .' '.($journey->members_count === 1 ? 'is' : 'are').' in this journey';

                    if ($isMember && $others === 0) {
                        $who = 'You are the first family in this journey';
                    }

                    if (! $isMember && $journey->members_count === 0) {
                        $who = 'Nobody is in this journey yet';
                    }
                @endphp

                <li class="journey-row">
                    <div class="journey-row__text">
                        <p class="journey-row__title">{{ $journey->title }}</p>
                        <p class="journey-row__meta">{{ $who }}</p>
                    </div>

                    {{-- A POST, because joining is what makes this family
                         visible to the others in here. --}}
                    <form method="POST"
                          action="{{ $isMember ? route('journeys.leave', $journey) : route('journeys.join', $journey) }}">
                        @csrf

                        @if ($isMember)
                            <button class="journey-row__joined" type="submit">
                                <span aria-hidden="true">Joined</span>
                                <span class="visually-hidden">Leave {{ $journey->title }}</span>
                            </button>
                        @else
                            <button class="button button--outline button--small" type="submit">
                                Join<span class="visually-hidden"> {{ $journey->title }}</span>
                            </button>
                        @endif
                    </form>
                </li>
            @endforeach
        </ul>
    </section>

</x-layouts.site>
