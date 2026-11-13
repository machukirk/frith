<x-layouts.site :title="$journey->title.' · Frith'" :noindex="true">

    <section class="page-head page-head--detail">
        <div class="page-head__inner">
            <p class="eyebrow">Journey</p>
            <h1 class="heading page-head__title">{{ $journey->title }}</h1>
            @if ($journey->blurb)
                <p class="page-head__standfirst">{{ $journey->blurb }}</p>
            @endif
        </div>
    </section>

    <section class="wrap family-list">
        @if (session('status'))
            <p class="journey-list__status" role="status">{{ session('status') }}</p>
        @endif

        <div class="family-list__head">
            <h2 class="family-list__heading">Families in this Journey</h2>
            <p class="family-list__sort">Most in common first</p>
        </div>

        @if ($cards->isEmpty())
            <p class="family-list__empty">
                Nobody else is in here yet. You will see them as they join, and they
                will see you.
            </p>
        @else
            <ul class="family-list__items">
                @foreach ($cards as $card)
                    <x-family.card :card="$card" :journey="$journey" />
                @endforeach
            </ul>
        @endif

        {{-- The way out, on the screen somebody is actually looking at. The
             directory's "Joined" pill does the same thing, which is no help
             to a family who has opened the Journey and found it is not for
             them.

             The panel is the confirmation: it says what leaving does before
             anybody presses anything. Considered and rejected: hiding it
             behind a <details>, which would tuck the one sentence that
             matters out of sight to save a press. --}}
        <div class="panel-stack__section">
            <section class="panel">
                <h2 class="panel__title">Not the right Journey?</h2>
                <p class="panel__body">
                    Leaving stops your profile being visible to the families in
                    {{ $journey->title }}.
                    Anyone you have already connected with stays connected,
                    and your messages stay as they are.
                    You can join again whenever you like.
                </p>

                <form class="action-row action-row--bare" method="POST" action="{{ route('journeys.leave', $journey) }}">
                    @csrf
                    <button class="button button--quiet button--small" type="submit">
                        Leave {{ $journey->title }}
                    </button>
                </form>
            </section>
        </div>

        <p class="family-list__back">
            <a href="{{ route('journeys') }}">&larr; All Journeys</a>
        </p>
    </section>

</x-layouts.site>
