<x-layouts.site title="Messages — Frith" :noindex="true">

    <section class="wrap page-head">
        <div class="page-head__inner">
            <h1 class="heading page-head__title">Messages</h1>
        </div>
    </section>

    <section class="wrap family-list">
        @if ($conversations->isEmpty())
            <p class="family-list__empty">
                No conversations yet. They start when another family says hello back.
                <a href="{{ route('journeys') }}">Have a look at your Journeys</a>.
            </p>
        @else
            <ul class="family-list__items">
                @foreach ($conversations as $connection)
                    @php
                        $other = $connection->otherThan($family);
                        $last = $connection->messages->last();
                        $unread = $connection->messages
                            ->where('sender_id', '!=', $family->id)
                            ->whereNull('read_at')
                            ->count();
                    @endphp

                    <li class="family-card">
                        <a class="family-card__who" href="{{ route('messages.show', $connection) }}">
                            <span class="family-card__avatar" aria-hidden="true">{{ $other->initial() }}</span>
                            <span>
                                <span class="family-card__name">{{ $other->displayName() }}</span>
                                <span class="family-card__meta">
                                    {{ $last ? \Illuminate\Support\Str::limit($last->body, 54) : 'Connected. Nobody has said anything yet.' }}
                                </span>
                            </span>
                        </a>

                        @if ($unread)
                            <p class="family-card__unread">
                                {{ $unread }} new<span class="visually-hidden"> from {{ $other->displayName() }}</span>
                            </p>
                        @endif
                    </li>
                @endforeach
            </ul>
        @endif
    </section>

</x-layouts.site>
