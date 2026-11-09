<x-layouts.site :title="$journey->title.' — Frith'" :noindex="true">

    <section class="wrap page-head">
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

        <p class="family-list__back">
            <a href="{{ route('journeys') }}">&larr; All Journeys</a>
        </p>
    </section>

</x-layouts.site>
