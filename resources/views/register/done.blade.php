<x-layouts.frith title="You’re a Frith Founder" :noindex="true">

    <main class="site-main gutter slim" id="main">
        <div class="slim__inner">

            @if ($registration->founder_number)
                <p class="founder-badge">
                    <span class="founder-badge__mark" aria-hidden="true">&#9679;</span>
                    Frith Founder&nbsp;#{{ $registration->founder_number }}
                </p>
            @endif

            <h1 class="slim__heading">Thank you, {{ $registration->first_name }}.</h1>

            <p class="slim__body">
                You’re a Frith Founder. That means you’re in from the beginning, and Frith’s
                premium features stay free for your family for good.
            </p>

            <div class="notice">
                <p class="notice__heading">
                    <span class="notice__mark" aria-hidden="true">&check;</span>What happens now
                </p>
                <p class="notice__body">
                    Nothing to do. We’ll email you at <strong>{{ $registration->email }}</strong> when
                    Frith opens, and you can change any of your answers then.
                </p>
                <p class="notice__body">
                    If anything above looks wrong, or you’d rather we forgot all of it, write to
                    <a href="mailto:{{ config('frith.company.contact_email') }}">{{ config('frith.company.contact_email') }}</a>
                    and a person will sort it out.
                </p>
            </div>

            <a class="btn btn--link" href="{{ route('coming-soon') }}">Back to the homepage</a>
        </div>
    </main>

</x-layouts.frith>
