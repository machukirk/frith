<x-layouts.site title="Requests · Frith" :noindex="true">

    <section class="wrap page-head">
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
                                <span class="family-card__common">
                                    {{ $card->commonality() }}@if ($connection->journey) &middot; via {{ $connection->journey->title }}@endif
                                </span>
                            </span>
                        </a>

                        <div class="family-card__pair">
                            <form method="POST" action="{{ route('connections.accept', $connection) }}">
                                @csrf
                                <button class="button button--primary button--small" type="submit">
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
    </section>

</x-layouts.site>
