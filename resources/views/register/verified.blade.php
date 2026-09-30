<x-layouts.frith title="Your email is confirmed" :noindex="true">

    <main class="site-main gutter slim" id="main">
        <div class="slim__inner">

            @if ($registration->founder_number)
                <p class="founder-badge">
                    <span class="founder-badge__mark" aria-hidden="true">&#9679;</span>
                    Frith Founder&nbsp;#{{ $registration->founder_number }}
                </p>
            @endif

            <h1 class="slim__heading">That’s everything, {{ $registration->first_name }}.</h1>

            <p class="slim__body">
                Your email is confirmed, so we can reach you when Frith opens. There is
                nothing else to do.
            </p>

            @unless ($registration->isComplete())
                <div class="notice">
                    <p class="notice__heading">
                        <span class="notice__mark" aria-hidden="true">&check;</span>Want to add a bit more?
                    </p>
                    <p class="notice__body">
                        You stopped part-way through the questions, which is completely fine —
                        you are registered as you are. The more you tell us, the better we can
                        match you when we open.
                    </p>
                    <p class="notice__body">
                        <a href="{{ route('register.start') }}">Pick up where you left off</a>
                    </p>
                </div>
            @endunless

            <a class="btn btn--link" href="{{ route('home') }}">Back to the homepage</a>
        </div>
    </main>

</x-layouts.frith>
