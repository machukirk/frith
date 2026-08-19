<x-register.shell
    heading="Where are you based?"
    standfirst="Just the first part of your postcode — the bit before the space."
    :step="$stepNumber" :total="$totalSteps" :previous="$previous"
>
    <form method="POST" action="{{ route('register.step.store', $step) }}" novalidate>
        @csrf

        <x-register.field
            name="postcode_outcode"
            label="First part of your postcode"
            :value="old('postcode_outcode', $registration?->postcode_outcode)"
            placeholder="SS9"
            autocomplete="postal-code"
            required
            class="field__input--short"
            help="We use this to find families near you. It is never shown on your profile, and we never ask for the rest of it."
        />

        <button class="btn" type="submit">Continue</button>
    </form>
</x-register.shell>
