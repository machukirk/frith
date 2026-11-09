<x-layouts.site title="Your village — Frith" :noindex="true">

    <section class="wrap page-head">
        <div class="page-head__inner">
            <h1 class="heading page-head__title">Your village</h1>
        </div>
    </section>

    <section class="wrap village">
        @if (session('status'))
            <p class="account__status" role="status">{{ session('status') }}</p>
        @endif

        <h2 class="village__heading">Journeys you have joined</h2>

        @if ($joined->isEmpty())
            <div class="village__empty">
                <p>
                    You have not joined a Journey yet. Joining one makes your profile
                    visible to the families in it, and nowhere else. It is how most
                    families find each other.
                </p>
                <p class="village__empty-action">
                    <a class="button button--primary button--small" href="{{ route('journeys') }}">
                        Browse all {{ $rest->count() }} Journeys
                    </a>
                </p>
            </div>
        @else
            <ul class="village__journeys">
                @foreach ($joined as $journey)
                    @php
                        $others = $journey->members_count - 1;
                        // Up to six dots, then the rest as a number. A row of
                        // dots is how many families are in here, not a count
                        // of anything unread — there are no unread counts.
                        $dots = min(6, $journey->members_count);
                        $overflow = $journey->members_count - $dots;
                    @endphp

                    <li class="village-card">
                        <p class="village-card__title">{{ $journey->title }}</p>

                        <p class="village-card__dots">
                            @for ($i = 0; $i < $dots; $i++)
                                <span class="village-card__dot" aria-hidden="true"></span>
                            @endfor
                            @if ($overflow > 0)
                                <span class="village-card__overflow" aria-hidden="true">+{{ $overflow }}</span>
                            @endif
                        </p>

                        <p class="village-card__meta">
                            {{ $others === 0
                                ? 'You are the first family in this journey'
                                : 'You and '.$others.' '.\Illuminate\Support\Str::plural('other', $others).' are in this journey' }}
                        </p>

                        <p class="village-card__action">
                            <a href="{{ route('journeys.show', $journey) }}">
                                Open journey<span class="visually-hidden">: {{ $journey->title }}</span>
                            </a>
                        </p>
                    </li>
                @endforeach

                @if ($rest->isNotEmpty())
                    <li class="village-card village-card--browse">
                        <p class="village-card__title">
                            <a href="{{ route('journeys') }}">Browse all Journeys</a>
                        </p>
                        <p class="village-card__meta">
                            {{ $rest->take(4)->pluck('title')->implode(' · ') }}
                        </p>
                    </li>
                @endif
            </ul>
        @endif

        {{-- The nudge. It can be dismissed, dismissing it is a real answer, and
             it cannot come back for another few months either way. --}}
        @if ($review)
            @php
                $since = $family->experiencesLastTouched()?->format('F');

                $nudge = $since
                    ? 'You last updated what your family is navigating in '.$since.'. Has anything changed?'
                    : 'It has been a while since you told us what your family is navigating. Has anything changed?';
            @endphp

            <div class="nudge">
                <span class="nudge__mark" aria-hidden="true">i</span>

                <p class="nudge__text">{{ $nudge }}</p>

                <a class="nudge__action" href="{{ route('account.experiences.review') }}">Take a look</a>

                <form method="POST" action="{{ route('account.experiences.snooze') }}">
                    @csrf
                    <button class="nudge__dismiss" type="submit">Not now</button>
                </form>
            </div>
        @endif

        {{-- Anything waiting on this family comes before anything new to look
             at. A reply somebody is owed is more use than a suggestion. --}}
        @if ($waitingOnYou > 0 || $waitingOnThem > 0)
            <div class="waiting">
                <span class="waiting__mark" aria-hidden="true">
                    <svg viewBox="0 0 24 24" width="19" height="19" fill="none" stroke="currentColor"
                         stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M12 20.3C7 16.8 3.8 14 3.8 10.3A4.4 4.4 0 0 1 12 7.9a4.4 4.4 0 0 1 8.2 2.4c0 3.7-3.2 6.5-8.2 10Z"/>
                    </svg>
                </span>

                @php
                    $theirs = $waitingOnYou === 1
                        ? '1 family is waiting to hear from you'
                        : $waitingOnYou.' families are waiting to hear from you';

                    $yours = $waitingOnThem === 1
                        ? 'And 1 of your requests has not been answered yet.'
                        : 'And '.$waitingOnThem.' of your requests have not been answered yet.';
                @endphp

                <div class="waiting__text">
                    <p class="waiting__title">
                        {{ $waitingOnYou > 0 ? $theirs : 'Your requests are still open' }}
                    </p>

                    @if ($waitingOnThem > 0)
                        <p class="waiting__sub">{{ $yours }}</p>
                    @endif
                </div>

                <a class="button button--primary button--small waiting__action" href="{{ route('requests') }}">
                    Go to Requests
                </a>
            </div>
        @endif

        @if ($connected->isNotEmpty())
            <div class="village__section-head">
                <h2 class="village__heading village__heading--inline">Families you are connected with</h2>
                <p class="village__count">
                    {{ $connected->count() }} {{ Str::plural('family', $connected->count()) }}
                </p>
            </div>

            <ul class="connected">
                @foreach ($connected as $connection)
                    @php $other = $connection->otherThan($family); @endphp

                    <li class="connected__item">
                        <a class="connected__link" href="{{ route('messages.show', $connection) }}">
                            <span class="family-card__avatar" aria-hidden="true">{{ $other->initial() }}</span>
                            <span>
                                <span class="family-card__name">{{ $other->displayName() }}</span>
                                <span class="family-card__meta">
                                    {{ $other->area() }}<br>
                                    {{ $connection->lastSpokeLabel($family) }}
                                </span>
                            </span>
                        </a>
                    </li>
                @endforeach
            </ul>
        @endif

        <h2 class="village__heading village__heading--later">Families suggested for you</h2>

        @if ($suggested->isEmpty())
            <p class="family-list__empty">
                @if ($joined->isEmpty())
                    Nobody yet, because suggestions come from the Journeys you are in.
                    <a href="{{ route('journeys') }}">Joining one</a> is what puts you
                    among families going through the same thing.
                @else
                    Nobody new just now. Everybody in your Journeys is either somebody
                    you have already spoken to or somebody you have answered. More
                    families join every week, and this fills up on its own.
                @endif
            </p>
        @else
            <ul class="family-list__items">
                @foreach ($suggested as $card)
                    <x-family.card :card="$card" />
                @endforeach
            </ul>
        @endif
    </section>

</x-layouts.site>
