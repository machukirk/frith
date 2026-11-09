@props([
    'heading',
    'standfirst' => null,
    'tag' => null,
    'current' => 'profile',
    'title' => null,
])

@php
    // The account's own rail. Two of these lead out to screens that are not
    // part of the account, Requests and Journeys, because a family looking
    // for "what have I asked for" looks here first, and sending them to a
    // dead end to be told it lives elsewhere is worse than a link.
    $sections = [
        'profile' => ['label' => 'Profile', 'url' => route('account')],
        'experiences' => ['label' => 'Your experiences', 'url' => route('account.experiences.review')],
        'badges' => ['label' => 'Badges', 'url' => route('account.badges')],
        'requests' => ['label' => 'Requests', 'url' => route('requests')],
        'journeys' => ['label' => 'Journeys', 'url' => route('journeys')],
        'notifications' => ['label' => 'Notifications', 'url' => route('account.notifications')],
        'plus' => ['label' => 'Frith+ membership', 'url' => route('frith-plus')],
        'blocked' => ['label' => 'Blocked families', 'url' => route('account.data').'#blocked'],
        'data' => ['label' => 'Your data', 'url' => route('account.data')],
    ];
@endphp

<x-layouts.site :title="$title ?? $heading.' — Frith'" :noindex="true">

    <div class="wrap account">
        <nav class="account__rail" aria-label="Your account">
            <h2 class="account__rail-title">Your account</h2>

            <ul class="account__rail-list">
                @foreach ($sections as $key => $section)
                    <li>
                        <a class="account__rail-link @if ($key === $current) account__rail-link--current @endif"
                           href="{{ $section['url'] }}"
                           @if ($key === $current) aria-current="page" @endif>
                            {{ $section['label'] }}
                        </a>
                    </li>
                @endforeach
            </ul>
        </nav>

        <div class="account__body">
            <div class="account__head">
                <h1 class="heading account__title">
                    {{ $heading }}
                    @if ($tag)
                        <span class="account__tag">{{ $tag }}</span>
                    @endif
                </h1>

                @if ($standfirst)
                    <p class="account__standfirst">{{ $standfirst }}</p>
                @endif
            </div>

            @if (session('status'))
                <p class="account__status" role="status">{{ session('status') }}</p>
            @endif

            {{ $slot }}
        </div>
    </div>

</x-layouts.site>
