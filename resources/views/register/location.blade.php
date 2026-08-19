@php $c = \App\Support\StepContent::for('location'); @endphp

<x-register.shell
    :heading="$c->heading()"
    :standfirst="$c->standfirst()"
    :private="$c->isPrivate()"
    :step="$stepNumber" :total="$totalSteps" :previous="$previous"
>
    <form method="POST" action="{{ route('register.step.store', $step) }}" novalidate>
        @csrf

        <x-register.field
            name="postcode_outcode"
            :label="$c->label('postcode_outcode', 'First part of your postcode')"
            :help="$c->help('postcode_outcode')"
            :placeholder="$c->placeholder('postcode_outcode')"
            :value="old('postcode_outcode', $registration?->postcode_outcode)"
            autocomplete="postal-code"
            required
            class="field__input--short"
        />

        <button class="btn" type="submit">Continue</button>
    </form>
</x-register.shell>
