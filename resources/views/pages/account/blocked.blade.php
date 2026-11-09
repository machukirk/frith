<x-account.shell
    :heading="'Blocked: '.$block->blocked->displayName()"
    current="blocked"
>
    <div class="panel">
        <h2 class="panel__title">What we have</h2>

        <dl class="detail-list">
            <div class="detail-list__row">
                <dt class="detail-list__key">Blocked</dt>
                <dd class="detail-list__value">{{ $block->created_at->format('j F Y') }}</dd>
            </div>

            <div class="detail-list__row">
                <dt class="detail-list__key">Area</dt>
                <dd class="detail-list__value">{{ $block->blocked->area() ?: 'Not given' }}</dd>
            </div>

            @if ($report)
                <div class="detail-list__row">
                    <dt class="detail-list__key">Reported as</dt>
                    <dd class="detail-list__value">{{ $reasons[$report->reason] ?? $report->reason }}</dd>
                </div>

                @if ($report->note)
                    <div class="detail-list__row">
                        <dt class="detail-list__key">What you wrote</dt>
                        <dd class="detail-list__value">{{ $report->note }}</dd>
                    </div>
                @endif

                <div class="detail-list__row">
                    <dt class="detail-list__key">Read by a person</dt>
                    <dd class="detail-list__value">
                        {{ $report->handled_at ? 'Yes, on '.$report->handled_at->format('j F Y') : 'Not yet' }}
                    </dd>
                </div>
            @endif
        </dl>
    </div>

    @unless ($report)
        <p class="account__foot-note">
            You blocked them without filing a report, which is entirely allowed:
            blocking never needs a reason. If something happened that we should know
            about, <a href="{{ route('reporting') }}">tell us</a> and a person reads it.
        </p>
    @endunless

    <div class="review-actions">
        <form method="POST" action="{{ route('account.unblock', $block->blocked->public_id) }}">
            @csrf
            <button class="button button--quiet" type="submit">
                Unblock {{ $block->blocked->displayName() }}
            </button>
        </form>

        <a class="account-form__cancel" href="{{ route('account.data') }}">Back to your data</a>
    </div>
</x-account.shell>
