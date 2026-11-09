<x-account.shell
    heading="Your profile"
    standfirst="This is the only part other families see before you both agree to connect."
    current="profile"
>
    {{-- Them, as another family first sees them. Shown at the top so a change
         to any section below can be checked against the thing it changes. --}}
    <div class="account-identity">
        <span class="account-identity__avatar" aria-hidden="true">{{ $family->initial() }}</span>

        <div class="account-identity__who">
            <p class="account-identity__name">{{ $family->displayName() }}</p>
            <p class="account-identity__meta">
                {{ $family->area() }}@if ($family->children->isNotEmpty()) &middot; {{ \App\Support\Words::count($family->children->count()) }} {{ Str::plural('child', $family->children->count()) }} (aged {{ $family->children->map->age()->implode(', ') }})@endif
            </p>

            @if ($badges->isNotEmpty())
                <ul class="badge-row">
                    @foreach ($badges as $badge)
                        <li class="badge-row__item @if ($badge['slug'] === 'first-frith-family') badge-row__item--founder @endif">
                            {{ $badge['title'] }}
                        </li>
                    @endforeach
                </ul>
            @endif
        </div>

        <a class="button button--quiet button--small" href="{{ route('account.edit', 'name') }}">
            Edit<span class="visually-hidden"> your name and area</span>
        </a>
    </div>

    <div class="account-progress">
        <p class="account-progress__tally">Your profile is {{ $tally }}</p>
        <p class="account-progress__note">
            The more you share, the more meaningful your connections can be.
            Nothing here is required.
        </p>
    </div>

    <ul class="section-list">
        @foreach ($sections as $section)
            <li class="section-list__item">
                <div class="section-list__text">
                    <p class="section-list__title">
                        {{ $section['title'] }}
                        @if ($section['private'])
                            <span class="section-list__private">Private</span>
                        @endif
                    </p>
                    <p class="section-list__summary @unless ($section['complete']) section-list__summary--empty @endunless">
                        {{ $section['summary'] }}
                    </p>
                </div>

                <a class="section-list__edit" href="{{ route('account.edit', $section['step']) }}">
                    {{ $section['complete'] ? 'Edit' : 'Add' }}<span class="visually-hidden"> {{ Str::lower($section['title']) }}</span>
                </a>
            </li>
        @endforeach
    </ul>

    <p class="account__out">
        <a href="{{ route('account.data') }}">Download or delete your data</a>
    </p>
</x-account.shell>
