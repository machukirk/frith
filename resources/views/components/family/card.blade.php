{{-- withdraw: the Requests screen passes it, because taking an ask back only
     makes sense on the list of your own asks. Everywhere else the row is read
     only. --}}
@props(['card', 'journey' => null, 'withdraw' => false, 'badges' => null])

@php
    $family = $card->family;

    // Badges belong to the village's suggestions, where a family nobody has
    // met is being introduced. The Journey list and the Requests list draw
    // none: there the name, what you have in common and the area are the
    // card. Left unset, that is what each of the three call sites gets;
    // pass :badges to say otherwise.
    $showBadges = $badges ?? ($journey === null && ! $withdraw);
@endphp

<li class="family-card">
    <a class="family-card__who" href="{{ route('family', $family->public_id) }}">
        <span class="family-card__avatar" aria-hidden="true">{{ $family->initial() }}</span>
        <span>
            {{-- What you have in common is a pill on the name's own row. --}}
            <span class="family-card__line">
                <span class="family-card__name">{{ $family->displayName() }}</span>
                <span class="family-card__common">{{ $card->commonality() }}</span>
            </span>
            <span class="family-card__meta">
                {{ $family->area() }}@if ($card->childSummary()) &middot; {{ $card->childSummary() }}@endif
            </span>

            @if ($showBadges && $card->badges()->isNotEmpty())
                <span class="badge-row">
                    @foreach ($card->badges() as $badge)
                        <span class="badge-row__item @if ($badge['slug'] === 'first-frith-family') badge-row__item--founder @endif">
                            {{ $badge['title'] }}
                        </span>
                    @endforeach
                </span>
            @endif
        </span>
    </a>

    <div class="family-card__action">
        @switch ($card->action())
            @case('connected')
                <a class="button button--outline button--small" href="{{ route('messages.show', $card->connection) }}">Message</a>
                @break

            @case('respond')
                <a class="button button--primary button--small" href="{{ route('requests') }}">They asked you</a>
                @break

            @case('waiting')
                <div class="family-card__pair">
                    <p class="family-card__waiting">Asked &middot; waiting</p>

                    {{-- The only way out of an ask nobody has answered. The
                         other family is told nothing, either way. --}}
                    @if ($withdraw && $card->connection)
                        <form method="POST" action="{{ route('connections.withdraw', $card->connection) }}">
                            @csrf
                            <button class="family-card__decline" type="submit">
                                Withdraw<span class="visually-hidden"> the request to {{ $family->displayName() }}</span>
                            </button>
                        </form>
                    @endif
                </div>
                @break

            @case('closed')
                <p class="family-card__waiting">Closed</p>
                @break

            @default
                <form method="POST" action="{{ route('connections.ask', $family->public_id) }}">
                    @csrf
                    @if ($journey)<input type="hidden" name="journey" value="{{ $journey->slug }}">@endif
                    <button class="button button--outline button--small" type="submit">
                        Ask to connect<span class="visually-hidden"> with {{ $family->displayName() }}</span>
                    </button>
                </form>
        @endswitch
    </div>
</li>
