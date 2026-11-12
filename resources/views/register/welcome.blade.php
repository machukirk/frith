@php
    $c = \App\Support\PageContent::for('join');
    $w = $c['welcome'];
    $registration = \App\Models\Registration::query()->find(session('registration.id'));
    // The table, not the home page's copy. This used to read a typed-out
    // list that is now gone, which left the panel naming no Journeys at all.
    $journeys = \App\Models\Journey::query()->offered()->take(3)->pluck('title');
@endphp

<x-layouts.site :title="$c['meta']['title']" :noindex="true">

    <div class="wizard wizard--done">
        <p class="wizard__tick" aria-hidden="true">&#10003;</p>

        <h1 class="heading wizard__heading">{{ $w['title'] }}</h1>

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

        <p class="wizard__hint wizard__footnote">{{ $w['footnote'] }}</p>
    </div>

</x-layouts.site>
