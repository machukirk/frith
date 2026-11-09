<x-account.shell
    heading="Does this still sound like your family?"
    current="experiences"
>
    <p class="account__standfirst account__standfirst--own">
        @if ($lastTouched)
            You last updated this in {{ $lastTouched->format('F') }}.
        @endif
        Things change: a diagnosis arrives, a placement settles, a new worry
        starts. Keeping this current is what keeps your matches useful.
    </p>

    @if ($areas->isEmpty())
        <p class="family-list__empty">
            You have not told us about any areas of family life yet. It is the part
            matching leans on most, so it is worth a few minutes if you have them.
            <a href="{{ route('account.edit', 'areas') }}">Add some</a>.
        </p>
    @else
        @foreach ($areas as $area)
            <section class="review-area">
                <div class="review-area__head">
                    <h2 class="review-area__title">{{ $area['label'] }}</h2>
                    <a class="review-area__edit" href="{{ route('account.edit', 'experiences') }}">
                        Edit<span class="visually-hidden"> {{ $area['label'] }}</span>
                    </a>
                </div>

                @if ($area['items'] === [])
                    <p class="review-area__none">Nothing recorded under this one.</p>
                @else
                    <ul class="review-list">
                        @foreach ($area['items'] as $item)
                            <li class="review-list__item">
                                <span class="review-list__tick" aria-hidden="true"></span>
                                {{ $item }}
                            </li>
                        @endforeach
                    </ul>
                @endif
            </section>
        @endforeach

        {{-- Two answers, weighted the same. "Still us" is a confirmation and
             buys the same quiet as a change, so neither is the cheap one. --}}
        <div class="review-actions">
            <form method="POST" action="{{ route('account.experiences.confirm') }}">
                @csrf
                <button class="button button--primary" type="submit">Yes, this is still us</button>
            </form>

            <a class="button button--quiet" href="{{ route('account.edit', 'experiences') }}">
                Something has changed
            </a>
        </div>
    @endif

    <p class="account__foot-note">
        We will ask again in about {{ config('frith.review.months') }} months. You can
        turn these reminders off in <a href="{{ route('account.notifications') }}">your
        notifications</a>, and nothing changes if you ignore them.
    </p>
</x-account.shell>
