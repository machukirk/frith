@php
    $c = \App\Support\PageContent::for('join');
    $w = $c['welcome'];
    $registration = \App\Models\Registration::query()->find(session('registration.id'));
    // The table, not the home page's copy. This used to read a typed-out
    // list that is now gone, which left the panel naming no Journeys at all.
    $journeys = \App\Models\Journey::query()->offered()->take(3)->pluck('title');

    // This screen used to tell every family it was a First Frith Family and
    // promise them Frith+ for good. Only the first hundred registrations get a
    // founder number: after that assignFounderNumber() returns without writing
    // one, so family 101 was told they held a status the database denies, and
    // promised a membership hasPlus() would refuse. The very next screen in the
    // same flow, register/verified.blade.php, has always guarded on this.
    //
    // isFounder() reads the column, which is the point. A count of rows would
    // be wrong twice over: a family who leaves takes their number with them,
    // so counting would issue a hundred and first, and a family reloading this
    // screen a year later would be told something different from the number
    // actually written against them.
    //
    // Null registration takes the quieter wording as well. Claiming a status
    // we cannot check is the failure that is worth avoiding here.
    $isFounder = (bool) $registration?->isFounder();
    $title = $isFounder ? $w['title'] : $w['title_if_not_a_founder'];
    $footnote = $isFounder ? $w['footnote'] : $w['footnote_if_not_a_founder'];
    $metaTitle = $isFounder ? $c['meta']['title'] : $c['meta']['title_if_not_a_founder'];
@endphp

<x-layouts.site :title="$metaTitle" :noindex="true">

    <div class="wizard wizard--done">
        <p class="wizard__tick" aria-hidden="true">&#10003;</p>

        <h1 class="heading wizard__heading">{{ $title }}</h1>

        <p class="wizard__standfirst">{{ $w['standfirst'] }}</p>

        @if ($registration?->email)
            {{-- Set apart, and in a monospace face, because the address is the
                 one thing on this screen somebody needs to read character by
                 character. A typo here is why they never hear from us. --}}
            <p class="wizard__launch">
                {!! str(e($w['launch_line']))->replace(
                    ':email',
                    '<span class="wizard__address">'.e($registration->email).'</span>',
                ) !!}
            </p>
        @endif

        <div class="panel panel--ringed">
            <p class="panel__title">{{ $w['journeys_title'] }}</p>
            <p class="panel__body">{{ $w['journeys_body'] }}</p>

            {{-- A taste of what is waiting, not a set of choices. The hi-fi
                 shows two of them already ticked; nobody has joined a Journey
                 at this point, and a tick against one they have not chosen is
                 telling them something untrue about their own account. --}}
            <ul class="wizard__journeys">
                @foreach ($journeys as $journey)
                    <li class="wizard__journey">{{ $journey }}</li>
                @endforeach
            </ul>

            <p class="wizard__panel-link">
                <a class="button button--outline button--small" href="{{ \App\Support\SiteNavigation::url('journeys') }}">
                    {{ str_replace(':count', \App\Support\Words::count(\App\Support\JourneyBoard::count()), $w['journeys_link']) }}
                </a>
            </p>
        </div>

        {{-- The village is behind the founder guard, and finishing the form
             does not sign anybody in: the emailed link is what proves the
             address is theirs. So this button used to send a family who had
             just registered to a login screen they had no password for, at
             the end of the one flow that must not have a dead end in it.
             A family who has already confirmed gets the button; everybody
             else gets told what is actually happening next. --}}
        @if (auth('founder')->check())
            <div class="wizard__actions wizard__actions--spaced">
                <a class="button button--primary" href="{{ route('village') }}">{{ $w['button'] }}</a>
            </div>
        @else
            <div class="note note--standalone wizard__next">
                {{ str_replace(':email', $registration?->email ?? 'your inbox', $w['next']) }}
            </div>
        @endif

        <p class="wizard__hint wizard__footnote">{{ $footnote }}</p>
    </div>

</x-layouts.site>
