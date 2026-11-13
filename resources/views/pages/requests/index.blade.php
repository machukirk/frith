<x-layouts.site title="Requests · Frith" :noindex="true">

    <section class="page-head page-head--detail">
        <div class="page-head__inner">
            <h1 class="heading page-head__title">Requests</h1>
            <p class="page-head__standfirst">
                Families who have asked to connect with you, and the ones you are
                waiting on. Nothing here is urgent. Take as long as you like.
            </p>
        </div>
    </section>

    <section class="wrap family-list">
        @if (session('status'))
            <p class="journey-list__status" role="status">{{ session('status') }}</p>
        @endif

        {{-- The lists, then the context column beside them from lg.

             The design puts the h1 and standfirst inside the left column, so
             the rail's top edge lines up with the title. Keeping the shared
             page-head component means the rail starts below the standfirst
             instead. That is the cheaper trade: one screen's alignment
             against a header every app screen shares.

             Separately, the design draws "Asked you / You asked / Not for
             us" as an underlined tab row rather than three stacked headings.
             That is a structural difference, and not this change. --}}
        <div class="family-list__columns">
        <div>

        <h2 class="family-list__heading">Asked you <span class="family-list__tally">{{ $askedYou->count() }}</span></h2>

        @if ($askedYou->isEmpty())
            <p class="family-list__empty">Nobody is waiting on you.</p>
        @else
            <ul class="family-list__items">
                @foreach ($askedYou as $connection)
                    @php $card = $cardFor($connection); @endphp

                    <li class="family-card family-card--request">
                        <a class="family-card__who" href="{{ route('family', $card->family->public_id) }}">
                            <span class="family-card__avatar" aria-hidden="true">{{ $card->family->initial() }}</span>
                            <span>
                                <span class="family-card__name">{{ $card->family->displayName() }}</span>
                                <span class="family-card__meta">
                                    {{ $card->family->area() }}@if ($card->childSummary()) &middot; {{ $card->childSummary() }}@endif
                                </span>
                                <span class="family-card__chips">
                                    <span class="family-card__chip">{{ $card->commonality() }}</span>
                                    @if ($connection->journey)
                                        <span class="family-card__chip family-card__chip--journey">via {{ $connection->journey->title }}</span>
                                    @else
                                        <span class="family-card__chip family-card__chip--suggested">Suggested by Frith</span>
                                    @endif
                                </span>
                            </span>
                        </a>

                        <div class="family-card__pair">
                            <form method="POST" action="{{ route('connections.accept', $connection) }}">
                                @csrf
                                <button class="button button--accent button--small" type="submit">
                                    Say hello back<span class="visually-hidden"> to {{ $card->family->displayName() }}</span>
                                </button>
                            </form>

                            <form method="POST" action="{{ route('connections.decline', $connection) }}">
                                @csrf
                                <button class="family-card__decline" type="submit">
                                    Not for us<span class="visually-hidden">, {{ $card->family->displayName() }}</span>
                                </button>
                            </form>
                        </div>
                    </li>
                @endforeach
            </ul>
        @endif

        <h2 class="family-list__heading family-list__heading--later">
            You asked <span class="family-list__tally">{{ $youAsked->count() }}</span>
        </h2>

        @if ($youAsked->isEmpty())
            <p class="family-list__empty">You have not asked anybody yet.</p>
        @else
            {{-- The limit, said where it bites. It is enforced in
                 ConnectionController::store, and a family who hits it there
                 is told the same thing in the same words.

                 The count is always shown. The instruction is not, because a
                 family with one ask waiting is not being stopped from asking
                 anybody, and telling them to withdraw one first is simply
                 untrue. Do not fold the second sentence back into the first. --}}
            <p class="family-list__sort">
                {{ $youAsked->count() }} of {{ $limit }} waiting for a reply.
                @if ($youAsked->count() >= $limit)
                    Withdraw one, or wait for an answer, before you ask somebody else.
                @endif
            </p>

            <ul class="family-list__items">
                @foreach ($youAsked as $connection)
                    <x-family.card :card="$cardFor($connection)" :withdraw="true" />
                @endforeach
            </ul>
        @endif

        @if ($closed->isNotEmpty())
            <h2 class="family-list__heading family-list__heading--later">
                Not for us <span class="family-list__tally">{{ $closed->count() }}</span>
            </h2>
            <p class="family-list__empty">
                Closed, either way round. Nobody was told, and nothing more happens.
            </p>
        @endif

        </div>

        <aside class="requests-rail">
            <div class="requests-rail__card requests-rail__card--count">
                <p class="requests-rail__eyebrow">Requests you have made</p>

                <p class="requests-rail__count">
                    {{ $youAsked->count() }}<span class="requests-rail__count-of"> of {{ $limit }}</span>
                </p>

                {{-- One segment per ask you are allowed. The number is
                     already said twice above and below in words, so the
                     meter is decoration and is hidden. --}}
                <div class="requests-rail__meter" aria-hidden="true">
                    @for ($i = 1; $i <= $limit; $i++)
                        <span class="requests-rail__segment @if ($i <= $youAsked->count()) requests-rail__segment--used @endif"></span>
                    @endfor
                </div>

                {{-- Said in whole sentences, which means counting the
                     singular properly. A family with one ask open should not
                     be told "1 of yours are waiting", and a family nobody has
                     asked should not be told "0 families are waiting on you"
                     at all. --}}
                <p class="requests-rail__body">
                    {{ $youAsked->count() === 0 ? 'None' : $youAsked->count() }}
                    of yours {{ $youAsked->count() === 1 ? 'is' : 'are' }} waiting for a reply.
                    @if ($askedYou->count() === 1)
                        One family is waiting on you, and that does not count towards your limit.
                    @elseif ($askedYou->count() > 1)
                        {{ $askedYou->count() }} families are waiting on you, and those do not count towards your limit.
                    @endif
                </p>

                <p class="requests-rail__fine">
                    You can have up to {{ $limit }} requests of your own open at a time,
                    helping keep Frith focused on meaningful connections, rather than
                    numbers. Requests other families send you are never limited.
                </p>
            </div>

            <div class="requests-rail__card requests-rail__card--plus">
                <p class="requests-rail__title">Seeing who asked</p>
                <p class="requests-rail__copy">
                    On the free plan, requests arrive one at a time as a private
                    notification. Frith+ shows you everyone who has asked, so you can
                    choose rather than answer in order.
                </p>
                <a class="button button--accent button--small" href="{{ route('frith-plus') }}">Explore Frith+</a>
            </div>

            <div class="requests-rail__card">
                <p class="requests-rail__title">Saying no</p>
                <p class="requests-rail__copy">
                    &ldquo;Not for us&rdquo; needs no reason and sends no message. They
                    are never told, and they cannot ask you again. You do not owe
                    anyone an explanation.
                </p>
            </div>
        </aside>

        </div>
    </section>

</x-layouts.site>
