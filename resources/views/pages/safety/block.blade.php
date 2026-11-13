<x-layouts.site :title="'Block '.$other->displayName().' · Frith'" :noindex="true">

    <section class="auth">
        <div class="auth__inner">
            <h1 class="heading auth__title">Block {{ $other->displayName() }}</h1>
            <p class="auth__standfirst">
                No reason needed, and nothing is reported. You do not owe anyone an
                explanation for not wanting to talk.
            </p>

            {{-- The four lines are one answer to one question, so they are
                 drawn as a group rather than as loose copy on the page.

                 Statements, not choices, so they do not borrow the tickbox
                 from the forms and look like something to press. --}}
            <div class="safety-card">
                <p class="safety-card__title">What blocking does</p>
                <ul class="safety-list">
                    @foreach ([
                        'They cannot see your profile, find you in a Journey, or message you.',
                        'Your conversation disappears from both inboxes.',
                        'They are never told that you blocked them.',
                        'You can unblock from your account at any time.',
                    ] as $line)
                        <li class="safety-list__item">
                            <span class="safety-list__tick" aria-hidden="true">&#10003;</span>
                            <span>{{ $line }}</span>
                        </li>
                    @endforeach
                </ul>
            </div>

            {{-- Blocking and changing your mind on one row. Cancel is not
                 small print under a full-width button. --}}
            <div class="safety-actions">
                <form method="POST" action="{{ route('block.store', $other->public_id) }}">
                    @csrf
                    <button class="button button--primary" type="submit">Block this family</button>
                </form>

                <a class="safety-cancel" href="{{ route('family', $other->public_id) }}">Cancel</a>
            </div>

            <p class="auth__foot">
                If something happened that we should know about,
                <a href="{{ route('report', $other->public_id) }}">report it instead</a>
                and a person will read it.
            </p>
        </div>
    </section>

</x-layouts.site>
