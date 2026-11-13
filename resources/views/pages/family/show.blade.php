@php $family = $card->family; @endphp

<x-layouts.site :title="$family->displayName().' · Frith'" :noindex="true">

    <section class="profile">
        @if (session('status'))
            <p class="journey-list__status" role="status">{{ session('status') }}</p>
        @endif

        <div class="profile__head">
            <p class="profile__avatar" aria-hidden="true">{{ $family->initial() }}</p>
            <h1 class="heading profile__name">{{ $family->displayName() }}</h1>
            <p class="profile__area">{{ $family->area() }}</p>

            {{-- Only the badges they have chosen to show. What a badge means
                 to them, and who their first connection was with, stays on
                 their own screen. --}}
            @foreach ($card->badges() as $badge)
                @if ($badge['slug'] === 'first-frith-family')
                    <p class="profile__badge">
                        <x-site.icon name="star" class="profile__badge-icon" />
                        {{ $badge['title'] }}
                    </p>
                @endif
            @endforeach

            @php $others = $card->badges()->where('slug', '!=', 'first-frith-family'); @endphp

            @if ($others->isNotEmpty())
                <ul class="badge-row profile__badges">
                    @foreach ($others as $badge)
                        <li class="badge-row__item">{{ $badge['title'] }}</li>
                    @endforeach
                </ul>
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
                        They asked you. Answer in Requests
                    </a>
                    @break

                @case('waiting')
                    <p class="profile__waiting">You asked to connect. They will see it when they next look.</p>

                    {{-- The same way out as the Requests screen. The copy no
                         longer says there is nothing to do but wait, because
                         there is now something: taking it back. --}}
                    @if ($card->connection)
                        <form class="action-row action-row--bare" method="POST" action="{{ route('connections.withdraw', $card->connection) }}">
                            @csrf
                            <button class="button button--quiet button--block" type="submit">
                                Take back my request
                            </button>
                        </form>
                    @endif
                    @break

                @case('closed')
                    <p class="profile__waiting">This one is closed.</p>
                    @break

                @default
                    {{-- Two actions, one form, one route. The button says
                         which it is, so saying no needs no JavaScript and no
                         second endpoint. "Ask to connect" is first, so it is
                         also what Enter does. --}}
                    <form class="action-row action-row--bare" method="POST" action="{{ route('connections.ask', $family->public_id) }}">
                        @csrf
                        <button class="button button--accent button--block" type="submit" name="action" value="ask">
                            Ask to connect
                        </button>
                        <button class="button button--outline button--block" type="submit" name="action" value="not-for-us">
                            Not for us
                        </button>
                    </form>
            @endswitch
        </div>

        <p class="family-list__back">
            <a href="{{ route('village') }}">&larr; Back to your village</a>
        </p>

        {{-- Blocking and reporting are two different things, and the profile
             used to offer only the second. Somebody who simply never wants to
             see a family again was filing a safeguarding report to do it. --}}
        <p class="profile__report">
            <a href="{{ route('block', $family->public_id) }}">Block this family</a>.
            No reason needed, and they are never told.
            If something happened that we should know about,
            <a href="{{ route('report', $family->public_id) }}">report and block instead</a>,
            and a person will read it.
        </p>
    </section>

</x-layouts.site>
