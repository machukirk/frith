@props([
    'heading',
    'standfirst' => null,
    'step' => null,
    'total' => null,
    'previous' => null,
    'private' => false,
    'title' => null,
])

<x-layouts.bare :title="$title ?? $heading.' — Frith'">

    <div class="wizard">
        @if ($step && $total)
            <p class="wizard__count">Step {{ $step }} of {{ $total }}</p>
            <div class="wizard__track" role="progressbar"
                 aria-valuenow="{{ $step }}" aria-valuemin="1" aria-valuemax="{{ $total }}"
                 aria-label="Registration progress">
                <span class="wizard__bar" style="width: {{ round($step / $total * 100) }}%"></span>
            </div>
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
            <p class="wizard__back"><a href="{{ $previous }}">&larr; Back</a></p>
        @endif
    </div>

</x-layouts.bare>
