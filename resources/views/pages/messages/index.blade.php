<x-layouts.site title="Messages · Frith" :noindex="true">

    @if ($conversations->isEmpty())
        {{-- With nothing to list, the empty state is the page. It used to be a
             white card nested under a 40px "Messages" heading, which framed an
             absence as though it were content. The design goes straight from
             the header into a centred column, and so does this. --}}
        <section class="wrap outcome">
            <span class="outcome__mark" aria-hidden="true">
                <x-site.icon name="speech" width="26" height="26" />
            </span>

            <h1 class="heading outcome__title">Nothing here yet</h1>

            <p class="outcome__body">
                When you say hello to someone and they say hello back, your
                conversations will show up here. There is no rush, and nobody is
                waiting on you.
            </p>

            <div class="outcome__actions">
                <a class="button button--primary" href="{{ route('village') }}">
                    Find families near you
                </a>
                <a class="button button--outline" href="{{ route('journeys') }}">
                    Browse Journeys
                </a>
            </div>
        </section>
    @else
        <section class="page-head page-head--app">
            <div class="page-head__inner">
                <h1 class="heading page-head__title">Messages</h1>
            </div>
        </section>

        <section class="wrap family-list">
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

                    {{-- --conversation quietens the name to 500, --unread
                         puts it back to 600. The inbox is the only list that
                         draws its rows at two weights. --}}
                    <li class="family-card family-card--conversation @if ($unread) family-card--unread @endif">
                        <a class="family-card__who" href="{{ route('messages.show', $connection) }}">
                            <span class="family-card__avatar" aria-hidden="true">{{ $other->initial() }}</span>
                            <span>
                                <span class="family-card__name">{{ $other->displayName() }}</span>
                                <span class="family-card__meta">
                                    {{ $last ? \Illuminate\Support\Str::limit($last->body, 54) : 'Connected. Nobody has said anything yet.' }}
                                </span>
                            </span>
                        </a>

                        {{-- A mark, not a number. Frith counts nothing back at
                             anybody: the dot says there is something to read,
                             and the row itself says who from. --}}
                        @if ($unread)
                            <span class="family-card__unread" aria-hidden="true"></span>
                            <span class="visually-hidden">Unread messages from {{ $other->displayName() }}</span>
                        @endif
                    </li>
                @endforeach
            </ul>
        </section>
    @endif

</x-layouts.site>
