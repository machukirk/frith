<x-layouts.frith title="You’re off the list" :noindex="true">

    <main class="site-main gutter slim" id="main">
        <div class="slim__inner">
            <h1 class="slim__heading">You’re off the list.</h1>

            <p class="slim__body">
                We won’t email you about the launch. Nothing you did was wrong, and you’re
                welcome back whenever you like.
            </p>

            <p class="slim__body">
                If you’d rather we deleted everything you told us as well, write to
                <a href="mailto:{{ config('frith.company.contact_email') }}">{{ config('frith.company.contact_email') }}</a>
                and a person will do it.
            </p>

            {{-- Mail filters follow links, so this page can be reached without
                 anyone meaning to. Coming back is one tap, same as leaving. --}}
            <form method="POST" action="{{ $resubscribeUrl }}">
                @csrf
                <button class="btn" type="submit">Actually, keep me on the list</button>
            </form>
        </div>
    </main>

</x-layouts.frith>
