@props(['card', 'journey' => null])

@php $family = $card->family; @endphp

<li class="family-card">
    <a class="family-card__who" href="{{ route('family', $family->public_id) }}">
        <span class="family-card__avatar" aria-hidden="true">{{ $family->initial() }}</span>
        <span>
            <span class="family-card__name">{{ $family->displayName() }}</span>
            <span class="family-card__meta">
                {{ $family->area() }}@if ($card->childSummary()) &middot; {{ $card->childSummary() }}@endif
            </span>
            <span class="family-card__common">{{ $card->commonality() }}</span>
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
                <p class="family-card__waiting">Asked &middot; waiting</p>
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
