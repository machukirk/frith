@php
    $c = \App\Support\StepContent::for('support');
    $chosen = old('support_areas', $registration?->support_areas ?? []);
@endphp

<x-register.shell
    :heading="$c->heading()"
    :standfirst="$c->standfirst()"
    :private="$c->isPrivate()"
    :step="$stepNumber" :total="$totalSteps" :previous="$previous"
>
    <form method="POST" action="{{ route('register.step.store', $step) }}">
        @csrf

        <fieldset class="choices choices--two-up">
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
