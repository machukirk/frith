@php
    $c = \App\Support\StepContent::for('family');
    $chosen = old('family_structures', $registration?->family_structures ?? []);
@endphp

<x-register.shell
    :heading="$c->heading()"
    :standfirst="$c->standfirst()"
    :private="$c->isPrivate()"
    :step="$stepNumber" :total="$totalSteps" :previous="$previous"
>
    <form method="POST" action="{{ route('register.step.store', $step) }}">
        @csrf

        <fieldset class="choices">
            <legend class="visually-hidden">{{ $c->heading() }} Choose as many as apply.</legend>

            @foreach (\App\Support\Taxonomy::familyStructures() as $slug => $label)
                <x-register.choice
                    name="family_structures[]"
                    :value="$slug"
                    :label="$label"
                    :checked="in_array($slug, (array) $chosen, true)"
                />
            @endforeach
        </fieldset>

        <button class="btn" type="submit">Continue</button>
    </form>
</x-register.shell>
