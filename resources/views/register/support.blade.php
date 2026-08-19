@php $chosen = old('support_areas', $registration?->support_areas ?? []); @endphp

<x-register.shell
    heading="Which areas of family life would you like the most support with?"
    standfirst="Choose as many as fit. This is how we find you families who understand, so there are no wrong answers."
    :step="$stepNumber" :total="$totalSteps" :previous="$previous"
    :private="true"
>
    <form method="POST" action="{{ route('register.step.store', $step) }}">
        @csrf

        <fieldset class="choices">
            <legend class="visually-hidden">Areas of family life. Choose as many as apply.</legend>

            @foreach (\App\Support\Taxonomy::categories() as $slug => $category)
                <x-register.choice
                    name="support_areas[]"
                    :value="$slug"
                    :label="$category['label']"
                    :description="$category['description']"
                    :checked="in_array($slug, (array) $chosen, true)"
                />
            @endforeach
        </fieldset>

        <button class="btn" type="submit">Continue</button>

        <p class="wizard__note">You are registered from here. The next few questions are optional, and you can change any of this later.</p>
    </form>
</x-register.shell>
