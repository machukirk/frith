<x-layouts.site title="Messages · Frith" :noindex="true">

    <section class="wrap page-head">
        <div class="page-head__inner">
            <h1 class="heading page-head__title">Messages</h1>
        </div>
    </section>

    <section class="wrap family-list">
        @if ($conversations->isEmpty())
            <div class="village__empty">
                <p class="village__empty-title">Nothing here yet</p>
                <p>
                    When you say hello to someone and they say hello back, your
                    conversations will show up here. There is no rush, and nobody is
                    waiting on you.
                </p>
                <p class="village__empty-action">
                    <a class="button button--primary button--small" href="{{ route('village') }}">
                        Find families near you
                    </a>
                    <a class="button button--quiet button--small" href="{{ route('journeys') }}">
                        Browse Journeys
                    </a>
                </p>
            </div>
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
