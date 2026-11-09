<x-layouts.site title="We cannot find that page · Frith" :noindex="true">

    <section class="wrap outcome">
        <span class="outcome__mark outcome__mark--question" aria-hidden="true">?</span>

        <h1 class="heading outcome__title">We cannot find that page</h1>

        <p class="outcome__body">
            The link may be old, or we may have moved something. Nothing has gone
            wrong with your account.
        </p>

        <div class="outcome__actions">
            @auth('founder')
                <a class="button button--primary" href="{{ route('village') }}">Go to your village</a>
            @else
                <a class="button button--primary" href="{{ route('home') }}">Back to the home page</a>
            @endauth

            <a class="outcome__quiet" href="mailto:{{ config('frith.company.contact_email') }}?subject=A%20link%20that%20does%20not%20work">
                Email us about it
            </a>
        </div>
    </section>

</x-layouts.site>
