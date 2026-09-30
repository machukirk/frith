@php $c = \App\Support\StepContent::for('you'); @endphp

<x-register.shell
    :heading="$c->heading()"
    :standfirst="$c->standfirst()"
    :step="$stepNumber" :total="$totalSteps" :previous="$previous"
>
    <form class="wizard__form" method="POST" action="{{ route('register.step.store', $step) }}">
        @csrf
        <x-honeypot />

        <div class="field">
            <label class="field__label" for="first_name">{{ $c->label('first_name', 'Your name') }}</label>
            <input class="field__control" id="first_name" name="first_name" type="text"
                   value="{{ old('first_name', $registration?->first_name) }}"
                   autocomplete="given-name" required aria-describedby="first_name-help"
                   @error('first_name') aria-invalid="true" @enderror>
            <p class="field__help" id="first_name-help">{{ $c->help('first_name') }}</p>
            @error('first_name')<p class="field__error">{{ $message }}</p>@enderror
        </div>

        <div class="field">
            <label class="field__label" for="email">{{ $c->label('email', 'Your email') }}</label>
            <input class="field__control" id="email" name="email" type="email"
                   value="{{ old('email', $registration?->email) }}"
                   autocomplete="email" required aria-describedby="email-help"
                   @error('email') aria-invalid="true" @enderror>
            <p class="field__help" id="email-help">{{ $c->help('email') }}</p>
            @error('email')<p class="field__error">{{ $message }}</p>@enderror
        </div>

        <div class="field">
            <label class="field__label" for="postcode_outcode">{{ $c->label('postcode_outcode', 'Where are you based?') }}</label>
            <input class="field__control" id="postcode_outcode" name="postcode_outcode" type="text"
                   value="{{ old('postcode_outcode', $registration?->postcode_outcode) }}"
                   placeholder="{{ $c->placeholder('postcode_outcode') }}"
                   inputmode="text" autocomplete="postal-code" required aria-describedby="postcode-help"
                   @error('postcode_outcode') aria-invalid="true" @enderror>
            <p class="field__help" id="postcode-help">{{ $c->help('postcode_outcode') }}</p>
            @error('postcode_outcode')<p class="field__error">{{ $message }}</p>@enderror
        </div>

        {{-- Shown, not just recorded. Consent nobody read is not consent. --}}
        <p class="wizard__hint">{{ config('frith.consent.text') }}</p>

        <div class="wizard__actions">
            <button class="button button--primary" type="submit">Continue</button>
        </div>
    </form>
</x-register.shell>
