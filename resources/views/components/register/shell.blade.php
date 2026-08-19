{{--
    The frame every registration screen sits in.

    One question per screen, a progress bar that counts up rather than down,
    and a back link that never loses what was typed — the answers are already
    saved server-side by the time you can press it.
--}}
@props([
    'heading',
    'standfirst' => null,
    'step' => null,
    'total' => null,
    {{-- A URL, not a step slug: what comes before a screen is not always the
         entry before it in the flow. See App\Support\RegistrationFlow. --}}
    'previous' => null,
    'private' => false,
    'title' => null,
])

<x-layouts.frith :title="$title ?? $heading.' — Frith'" :noindex="true">

    <main class="site-main gutter" id="main">
        <div class="wizard">

            @if ($step && $total)
                <div class="wizard__progress">
                    <p class="wizard__count">Step {{ $step }} of {{ $total }}</p>
                    <div class="wizard__track" role="progressbar"
                         aria-valuenow="{{ $step }}" aria-valuemin="1" aria-valuemax="{{ $total }}"
                         aria-label="Registration progress">
                        <span class="wizard__bar" style="width: {{ round($step / $total * 100) }}%"></span>
                    </div>
                </div>
            @endif

            <div class="wizard__panel" data-wizard-panel>
                <h1 class="wizard__heading">{{ $heading }}</h1>

                @if ($standfirst)
                    <p class="wizard__standfirst">{{ $standfirst }}</p>
                @endif

                @if ($private)
                    {{-- Guidelines §07: state the safety where the worry is,
                         not in a policy page nobody opens. --}}
                    <p class="wizard__private">
                        <span class="wizard__private-mark" aria-hidden="true">&#9679;</span>
                        Only ever used to match you with families like yours. Never shown on your profile.
                    </p>
                @endif

                {{ $slot }}
            </div>

            @if ($previous)
                <p class="wizard__back">
                    <a href="{{ $previous }}">&larr; Back</a>
                </p>
            @endif

        </div>
    </main>

</x-layouts.frith>
