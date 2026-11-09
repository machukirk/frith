@php $family = $card->family; @endphp

<x-layouts.site :title="$family->displayName().' — Frith'" :noindex="true">

    <section class="profile">
        @if (session('status'))
            <p class="journey-list__status" role="status">{{ session('status') }}</p>
        @endif

        <div class="profile__head">
            <p class="profile__avatar" aria-hidden="true">{{ $family->initial() }}</p>
            <h1 class="heading profile__name">{{ $family->displayName() }}</h1>
            <p class="profile__area">{{ $family->area() }}</p>

            @if ($family->isFounder())
                <p class="profile__badge">
                    <x-site.icon name="star" class="profile__badge-icon" />
                    First Frith Family
                </p>
            @endif
        </div>

        @if ($card->childAges() !== [])
            <p class="profile__label">Children</p>
            <ul class="profile__children">
                @foreach ($card->childAges() as $age)
                    <li class="profile__child">Aged {{ $age }}</li>
                @endforeach
            </ul>
        @endif

        <p class="profile__common">You have {{ $card->commonality() }}.</p>

        <div class="profile__actions">
            @switch ($card->action())
                @case('connected')
                    <a class="button button--primary button--block" href="{{ route('messages.show', $card->connection) }}">
                        Message {{ $family->displayName() }}
                    </a>
                    @break

                @case('respond')
                    <a class="button button--primary button--block" href="{{ route('requests') }}">
                        They asked you &mdash; answer in Requests
                    </a>
                    @break

                @case('waiting')
                    <p class="profile__waiting">You asked to connect. Nothing to do but wait.</p>
                    @break

                @case('closed')
                    <p class="profile__waiting">This one is closed.</p>
                    @break

                @default
                    <form method="POST" action="{{ route('connections.ask', $family->public_id) }}">
                        @csrf
                        <button class="button button--primary button--block" type="submit">Ask to connect</button>
                    </form>
            @endswitch
        </div>

        <p class="profile__report">
            <a href="{{ route('report', $family->public_id) }}">Report and block this family</a>
        </p>
    </section>

</x-layouts.site>
