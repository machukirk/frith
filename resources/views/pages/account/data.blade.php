<x-account.shell
    heading="Your data"
    standfirst="Everything Frith holds about your family, and how to take it or remove it."
    current="data"
>
    <div class="panel-stack">
        <section class="panel">
            <h2 class="panel__title">Download a copy</h2>
            <p class="panel__body">
                A single file with your profile, your answers, your journeys and your
                messages. Usually ready within an hour.
            </p>

            @if ($family->data_requested_at)
                <p class="panel__body panel__body--done">
                    Asked for on {{ $family->data_requested_at->format('j F') }}. It
                    goes to {{ $family->email }}. If it has not arrived, write to
                    {{ config('frith.company.contact_email') }} and a person will chase it.
                </p>
            @else
                <form method="POST" action="{{ route('account.data.request') }}">
                    @csrf
                    <button class="button button--quiet button--small" type="submit">Request my data</button>
                </form>
            @endif
        </section>

        <section class="panel">
            <h2 class="panel__title">Leave Frith</h2>
            <p class="panel__body">
                Your profile disappears from journeys and search straight away.
                Messages you have sent stay in the other family's inbox: we cannot
                remove words from someone else's conversation. Everything else is
                deleted within {{ $graceDays }} days.
            </p>
            <p>
                <a class="button button--danger button--small" href="{{ route('account.delete') }}">
                    Delete my account
                </a>
            </p>
        </section>

        <section class="panel" id="blocked">
            <h2 class="panel__title">Blocked families</h2>

            @if ($blocks->isEmpty())
                <p class="family-list__empty">
                    You have not blocked anybody. If you ever need to, it takes one press
                    from their profile, needs no reason, and they are never told.
                </p>
            @else
                <p class="panel__body">
                    {{ ucfirst(\App\Support\Words::count($blocks->count())) }}
                    {{ Str::plural('family', $blocks->count()) }}
                    {{ $blocks->count() === 1 ? 'is' : 'are' }} blocked. They cannot see
                    your profile, find you in a Journey, or message you, and they are
                    never told. You can look back at who they were, in case you need to
                    remember why.
                </p>

                <ul class="family-list__items">
                    @foreach ($blocks as $block)
                        <li class="family-card">
                            <div class="family-card__who">
                                <span class="family-card__avatar" aria-hidden="true">{{ $block->blocked->initial() }}</span>
                                <span>
                                    <span class="family-card__name">{{ $block->blocked->displayName() }}</span>
                                    <span class="family-card__meta">
                                        Blocked {{ $block->created_at->format('j F Y') }}
                                    </span>
                                </span>
                            </div>

                            <div class="family-card__pair">
                                <a class="account__quiet-link" href="{{ route('account.blocked', $block->blocked->public_id) }}">
                                    View details<span class="visually-hidden"> for {{ $block->blocked->displayName() }}</span>
                                </a>

                                <form method="POST" action="{{ route('account.unblock', $block->blocked->public_id) }}">
                                    @csrf
                                    <button class="button button--quiet button--small" type="submit">
                                        Unblock<span class="visually-hidden"> {{ $block->blocked->displayName() }}</span>
                                    </button>
                                </form>
                            </div>
                        </li>
                    @endforeach
                </ul>
            @endif
        </section>
    </div>

    <p class="account__foot-note">
        Data requests answered within 30 days, usually much sooner.
    </p>
</x-account.shell>
