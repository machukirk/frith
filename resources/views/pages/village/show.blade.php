<x-layouts.site title="Your village — Frith" :noindex="true">

    <section class="wrap page-head">
        <div class="page-head__inner">
            <h1 class="heading page-head__title">Your village</h1>
        </div>
    </section>

    <section class="wrap village">
        <h2 class="village__heading">Journeys you have joined</h2>

        @if ($joined->isEmpty())
            <div class="village__empty">
                <p>
                    You have not joined a Journey yet. Joining one is what puts you
                    among families going through the same thing.
                </p>
                <p class="village__empty-action">
                    <a class="button button--primary button--small" href="{{ route('journeys') }}">
                        Browse all {{ $rest->count() }} Journeys
                    </a>
                </p>
            </div>
        @else
            <ul class="village__journeys">
                @foreach ($joined as $journey)
                    @php $others = $journey->members_count - 1; @endphp

                    <li class="village-card">
                        <p class="village-card__title">{{ $journey->title }}</p>
                        <p class="village-card__meta">
                            {{ $others === 0
                                ? 'You are the first family in this journey'
                                : 'You and '.$others.' '.\Illuminate\Support\Str::plural('other', $others).' are in this journey' }}
                        </p>
                        <p class="village-card__action">
                            <a class="action-link" href="{{ route('journeys') }}">
                                Open journey <span aria-hidden="true">&rarr;</span>
                            </a>
                        </p>
                    </li>
                @endforeach
            </ul>

            @if ($rest->isNotEmpty())
                <p class="village__browse">
                    <a href="{{ route('journeys') }}">Browse all Journeys</a>
                    <span class="village__browse-rest">
                        {{ $rest->take(4)->pluck('title')->implode(' · ') }}
                    </span>
                </p>
            @endif
        @endif
    </section>

</x-layouts.site>
