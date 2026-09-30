<x-layouts.site title="Your email is confirmed — Frith" :noindex="true">

    <div class="wizard">
        <p class="wizard__tick" aria-hidden="true">&#10003;</p>

        @if ($registration->founder_number)
            <p class="wizard__badge">
                <span aria-hidden="true">&#9679;</span>
                Frith Founder&nbsp;#{{ $registration->founder_number }}
            </p>
        @endif

        <h1 class="heading wizard__heading">That’s everything, {{ $registration->first_name }}.</h1>

        <p class="wizard__standfirst">
            Your email is confirmed, so we can reach you when Frith opens. There is
            nothing else to do.
        </p>

        @unless ($registration->isComplete())
            {{-- Said as an offer, not a chase. They are registered either way,
                 and the screen has just told them so. --}}
            <div class="panel">
                <p class="panel__title">Want to add a bit more?</p>
                <p class="panel__body">
                    You stopped part-way through the questions, which is completely fine —
                    you are registered as you are. The more you tell us, the better we can
                    match you when we open.
                </p>

                <p class="wizard__panel-link">
                    <a class="action-link" href="{{ route('register.start') }}">
                        Pick up where you left off <span aria-hidden="true">&rarr;</span>
                    </a>
                </p>
            </div>
        @endunless

        <div class="wizard__actions wizard__actions--spaced">
            <a class="button button--primary" href="{{ route('home') }}">Back to the homepage</a>
        </div>
    </div>

</x-layouts.site>
