@php
    $c = \App\Support\StepContent::for('areas');
    $chosen = old('support_areas', $registration?->support_areas ?? []);
@endphp

<x-register.shell
    :heading="$c->heading()"
    :standfirst="$c->standfirst()"
    :private="$c->isPrivate()"
    :step="$stepNumber" :total="$totalSteps" :previous="$previous"
>
    <form class="wizard__form" method="POST" action="{{ route('register.step.store', $step) }}">
        @csrf
        <x-honeypot />

        <fieldset class="area-cards">
            <legend class="visually-hidden">Areas of family life. Choose all that apply.</legend>

            @foreach (\App\Support\Taxonomy::categories() as $slug => $area)
                <label class="area-cards__item">
                    <input type="checkbox" name="support_areas[]" value="{{ $slug }}"
                           @checked(in_array($slug, (array) $chosen, true))>
                    <span class="area-cards__mark" aria-hidden="true"></span>
                    <span>
                        <span class="area-cards__title">{{ $area['label'] }}</span>
                        <span class="area-cards__detail">{{ $area['description'] }}</span>
                    </span>
                </label>
            @endforeach
        </fieldset>

        <div class="wizard__actions">
            <button class="button button--primary" type="submit" name="action" value="continue">Continue</button>

            {{-- A submit, not a link, because skipping has to clear anything
                 chosen on an earlier visit as well as move them on. --}}
            <button class="wizard__skip" type="submit" name="action" value="skip">Skip this section</button>
        </div>
    </form>
</x-register.shell>
