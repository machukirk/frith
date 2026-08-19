<x-register.shell
    :heading="$meta['label']"
    :standfirst="$meta['description']"
    :private="true"
    :title="$meta['label'].' — Frith'"
>
    <p class="wizard__count wizard__count--sub">Area {{ $position }} of {{ $total }}</p>

    <form method="POST" action="{{ route('register.experiences.store', $category) }}">
        @csrf

        <fieldset class="choices choices--compact">
            <legend class="visually-hidden">{{ $meta['label'] }} — choose anything that sounds like your family.</legend>

            @foreach ($meta['items'] as $slug => $label)
                <x-register.choice
                    name="items[]"
                    :value="$slug"
                    :label="$label"
                    :checked="in_array($slug, $selected, true)"
                />
            @endforeach
        </fieldset>

        <div class="wizard__actions">
            <button class="btn" type="submit" name="action" value="continue">
                {{ $position === $total ? 'Finish' : 'Continue' }}
            </button>
            {{-- The source spreadsheet asks for this explicitly, to limit
                 form-filling fatigue. Full contrast, not greyed out. --}}
            <button class="btn btn--quiet" type="submit" name="action" value="skip-all">Skip the rest</button>
        </div>
    </form>

    @if ($previous)
        <p class="wizard__back">
            <a href="{{ route('register.experiences.show', $previous) }}">&larr; Back</a>
        </p>
    @endif
</x-register.shell>
