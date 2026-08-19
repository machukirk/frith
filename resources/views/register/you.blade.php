<x-register.shell
    heading="What should we call you?"
    standfirst="Your first name is what other families will see. Your last name stays private."
    :step="$stepNumber" :total="$totalSteps" :previous="$previous"
>
    <form method="POST" action="{{ route('register.step.store', $step) }}" novalidate>
        @csrf
        <x-honeypot />

        <div class="field-row">
            <x-register.field name="first_name" label="First name" :value="old('first_name', $registration?->first_name)" autocomplete="given-name" required />
            <x-register.field name="last_name" label="Last name" :value="old('last_name', $registration?->last_name)" autocomplete="family-name" required />
        </div>

        <x-register.field
            name="email"
            type="email"
            label="Your email"
            :value="old('email', $registration?->email)"
            autocomplete="email"
            inputmode="email"
            required
            help="So we can tell you the moment Frith opens. One email, and you can leave any time."
        />

        <button class="btn" type="submit">Continue</button>
    </form>
</x-register.shell>
