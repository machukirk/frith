@php
    $c = \App\Support\StepContent::for('interests');

    $chosenInterests = old('interests', $registration?->interests ?? []);
    $chosenSupports = old('activity_supports', $registration?->activity_supports ?? []);
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
            <legend class="visually-hidden">What your family enjoys. Choose as many as apply, or none.</legend>

            @foreach (\App\Support\Taxonomy::interests() as $slug => $interest)
                <x-register.choice
                    name="interests[]"
                    :value="$slug"
                    :label="$interest['label']"
                    :description="$interest['description']"
                    :checked="in_array($slug, (array) $chosenInterests, true)"
                />
            @endforeach
        </fieldset>

        {{-- Always visible rather than revealed by the Other tickbox: this form
             works with no JavaScript at all. Anything written here counts as
             choosing Other, whether or not they also ticked it. --}}
        <x-register.field
            name="interests_other"
            :label="$c->label('interests_other', 'Tell us more')"
            :help="$c->help('interests_other')"
            :value="old('interests_other', $registration?->interests_other)"
            rows="3"
        />

        <fieldset class="choices choices--two-up">
            <legend class="choices__legend">{{ $c->label('activity_supports', 'Are there things that help you enjoy activities?') }}</legend>

            @foreach (\App\Support\Taxonomy::activitySupports() as $slug => $label)
                <x-register.choice
                    name="activity_supports[]"
                    :value="$slug"
                    :label="$label"
                    :checked="in_array($slug, (array) $chosenSupports, true)"
                />
            @endforeach
        </fieldset>

        <button class="btn" type="submit">Continue</button>

        <p class="wizard__note">All of this is optional — skip anything that does not fit, and you can change it later.</p>
    </form>
</x-register.shell>
