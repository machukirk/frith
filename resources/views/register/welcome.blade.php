@php
    $c = \App\Support\PageContent::for('join');
    $w = $c['welcome'];
    $registration = \App\Models\Registration::query()->find(session('registration.id'));
    $journeys = \App\Support\PageContent::get('home', 'journeys.items', []);
@endphp

<x-layouts.bare :title="$c['meta']['title']">

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
                @foreach (array_slice($journeys, 0, 3) as $journey)
                    <li class="wizard__journey">{{ $journey['title'] }}</li>
                @endforeach
            </ul>

            <p class="wizard__panel-link">
                <a class="button button--outline button--small" href="{{ \App\Support\SiteNavigation::url('journeys') }}">
                    {{ $w['journeys_link'] }}
                </a>
            </p>
        </div>

        <div class="wizard__actions wizard__actions--spaced">
            <a class="button button--primary" href="{{ \App\Support\SiteNavigation::url('village') }}">{{ $w['button'] }}</a>
        </div>

        <p class="wizard__hint wizard__footnote">{{ $w['footnote'] }}</p>
    </div>

</x-layouts.bare>
