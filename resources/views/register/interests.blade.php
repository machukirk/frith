@php
    $c = \App\Support\StepContent::for('interests');

    $groups = [
        ['key' => 'interests', 'options' => \App\Support\Taxonomy::interests(), 'chosen' => $registration?->interests ?? []],
        ['key' => 'activity_supports', 'options' => \App\Support\Taxonomy::activitySupports(), 'chosen' => $registration?->activity_supports ?? []],
        ['key' => 'connection_styles', 'options' => \App\Support\Taxonomy::connectionStyles(), 'chosen' => $registration?->connection_styles ?? []],
    ];
@endphp

<x-register.shell
    :heading="$c->heading()"
    :standfirst="$c->standfirst()"
    :step="$stepNumber" :total="$totalSteps" :previous="$previous"
>
    <form class="wizard__form" method="POST" action="{{ route('register.step.store', $step) }}">
        @csrf
        <x-honeypot />

        @foreach ($groups as $group)
            @php
                $options = collect($group['options'])
                    ->map(fn ($o) => is_array($o) ? $o['label'] : $o)
                    ->all();
            @endphp

            <div class="wizard__group">
                <p class="wizard__label">{{ $c->label($group['key']) }}</p>

                <x-register.pills :name="$group['key']"
                                  :options="$options"
                                  :chosen="old($group['key'], $group['chosen'])"
                                  :legend="$c->label($group['key'])" />

                @if ($c->help($group['key']))
                    <p class="wizard__hint">{{ $c->help($group['key']) }}</p>
                @endif

                {{-- Always visible rather than revealed by the Other pill: this
                     form works with no JavaScript at all. Anything written here
                     counts as choosing Other, whether or not they picked it. --}}
                @if ($group['key'] === 'interests')
                    <div class="field">
                        <label class="field__label" for="interests_other">{{ $c->label('interests_other', 'Tell us more') }}</label>
                        <textarea class="field__control" id="interests_other" name="interests_other" rows="3"
                                  aria-describedby="interests_other-help"
                                  @error('interests_other') aria-invalid="true" @enderror
                        >{{ old('interests_other', $registration?->interests_other) }}</textarea>
                        <p class="field__help" id="interests_other-help">{{ $c->help('interests_other') }}</p>
                        @error('interests_other')<p class="field__error">{{ $message }}</p>@enderror
                    </div>
                @endif
            </div>
        @endforeach

        <div class="wizard__actions">
            <button class="button button--primary" type="submit">Finish</button>
        </div>
    </form>
</x-register.shell>
