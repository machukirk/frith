<x-layouts.site title="You’re off the list — Frith" :noindex="true">

    <div class="wizard">
        <h1 class="heading wizard__heading">You’re off the list.</h1>

        <p class="wizard__standfirst">
            We won’t email you about the launch. Nothing you did was wrong, and you’re
            welcome back whenever you like.
        </p>

        <div class="note">
            If you’d rather we deleted everything you told us as well, write to
            <a href="mailto:{{ config('frith.company.contact_email') }}">{{ config('frith.company.contact_email') }}</a>
            and a person will do it.
        </div>

        {{-- Mail filters follow links, so this page can be reached without
             anyone meaning to. Coming back is one tap, same as leaving. --}}
        <form class="wizard__actions wizard__actions--spaced" method="POST" action="{{ $resubscribeUrl }}">
            @csrf
            <button class="button button--primary" type="submit">Actually, keep me on the list</button>
        </form>
    </div>

</x-layouts.site>
