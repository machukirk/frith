@props([
    'heading',
    'standfirst' => null,
    'step' => null,
    'total' => null,
    'previous' => null,
    'private' => false,
    'title' => null,
    'editing' => false,
])

<x-layouts.site :title="$title ?? $heading.' · Frith'" :noindex="true">

    <div class="wizard">
        @if ($step && $total)
            {{-- The hi-fi draws no rail on any of the six steps: the count goes
                 straight into the heading. The count is also the better thing
                 to leave standing. "Step 2 of 6" is read out as written, where
                 role="progressbar" would have replaced that sentence with a
                 bare number and told nobody what the number was of. --}}
            <p class="wizard__count">Step {{ $step }} of {{ $total }}</p>
        @endif

        <h1 class="heading wizard__heading">{{ $heading }}</h1>

        @if ($standfirst)
            <p class="wizard__standfirst">{{ $standfirst }}</p>
        @endif

        {{ $slot }}

        @if ($private)
            {{-- Guidelines §07: state the safety where the worry is, not in a
                 policy page nobody opens. --}}
            <p class="wizard__private">
                <span aria-hidden="true">&#9679;</span>
                Only ever used to match you with families like yours. Never shown on your profile.
            </p>
        @endif

        @if ($previous)
            <p class="wizard__back">
                <a href="{{ $previous }}">
                    &larr; {{ $editing ? 'Back to your profile without saving' : 'Back' }}
                </a>
            </p>
        @endif
    </div>

</x-layouts.site>
