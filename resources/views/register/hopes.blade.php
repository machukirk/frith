@php
    $c = \App\Support\StepContent::for('hopes');
    $chosen = old('hopes', $registration?->hopes ?? []);
@endphp

<x-register.shell
    :heading="$c->heading()"
    :standfirst="$c->standfirst()"
    :step="$stepNumber" :total="$totalSteps" :previous="$previous"
>
    <form class="wizard__form" method="POST" action="{{ route('register.step.store', $step) }}">
        @csrf
        <x-honeypot />

        <x-register.check-list name="hopes"
                               :options="\App\Support\Taxonomy::hopes()"
                               :chosen="$chosen"
                               legend="What you are hoping to find. Choose as many as you wish." />

        <p class="wizard__hint">{{ $c->help('hopes') }}</p>

        <div class="wizard__actions">
            <button class="button button--primary" type="submit">Continue</button>
        </div>
    </form>
</x-register.shell>
