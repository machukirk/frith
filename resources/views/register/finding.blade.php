@php
    $c = \App\Support\StepContent::for('finding');

    $questions = [
        ['key' => 'hopes', 'options' => \App\Support\Taxonomy::hopes()],
        ['key' => 'connection_styles', 'options' => \App\Support\Taxonomy::connectionStyles()],
        ['key' => 'family_preferences', 'options' => \App\Support\Taxonomy::familyPreferences()],
    ];
@endphp

<x-register.shell
    :heading="$c->heading()"
    :standfirst="$c->standfirst()"
    :private="$c->isPrivate()"
    :step="$stepNumber" :total="$totalSteps" :previous="$previous"
>
    <form method="POST" action="{{ route('register.step.store', $step) }}">
        @csrf

        @foreach ($questions as $question)
            @php
                $chosen = (array) old($question['key'], $registration?->{$question['key']} ?? []);
            @endphp

            <fieldset class="choices choices--two-up">
                <legend class="choices__legend">{{ $c->label($question['key']) }}</legend>

                @foreach ($question['options'] as $slug => $label)
                    <x-register.choice
                        :name="$question['key'].'[]'"
                        :value="$slug"
                        :label="$label"
                        :checked="in_array($slug, $chosen, true)"
                    />
                @endforeach
            </fieldset>
        @endforeach

        <button class="btn" type="submit">Finish</button>

        <p class="wizard__note">Every question here is optional. Skip anything that does not fit — you can add to it later.</p>
    </form>
</x-register.shell>
