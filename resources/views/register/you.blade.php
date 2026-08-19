@php $c = \App\Support\StepContent::for('you'); @endphp

<x-register.shell
    :heading="$c->heading()"
    :standfirst="$c->standfirst()"
    :private="$c->isPrivate()"
    :step="$stepNumber" :total="$totalSteps" :previous="$previous"
>
    <form method="POST" action="{{ route('register.step.store', $step) }}" novalidate>
        @csrf
        <x-honeypot />

        <x-register.field
            name="first_name"
            :label="$c->label('first_name', 'Your name')"
            :help="$c->help('first_name')"
            :value="old('first_name', $registration?->first_name)"
            autocomplete="given-name"
            required
        />

        <x-register.field
            name="email"
            type="email"
            :label="$c->label('email', 'Your email')"
            :help="$c->help('email')"
            :placeholder="$c->placeholder('email')"
            :value="old('email', $registration?->email)"
            autocomplete="email"
            inputmode="email"
            required
        />

        {{-- Shown, not just recorded. What gets stored against this
             registration is the sentence below, so it has to be a sentence
             they actually read before they press the button. --}}
        <p class="consent">{{ config('frith.consent.text') }}</p>

        <button class="btn" type="submit">Continue</button>
    </form>
</x-register.shell>
