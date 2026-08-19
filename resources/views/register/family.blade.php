@php $chosen = old('family_structures', $registration?->family_structures ?? []); @endphp

<x-register.shell
    heading="Who is part of your family?"
    standfirst="Families come in all shapes and sizes. Choose as many as fit — or skip it."
    :step="$stepNumber" :total="$totalSteps" :previous="$previous"
>
    <form method="POST" action="{{ route('register.step.store', $step) }}">
        @csrf

        <fieldset class="choices">
            <legend class="visually-hidden">Who is part of your family? Choose as many as apply.</legend>

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
