@php
    $c = \App\Support\StepContent::for('experiences');
    $areas = $registration?->orderedSupportAreas() ?? [];
    $chosen = old('items', $registration?->experiencesByCategory()->map->pluck('item')->map->all()->all() ?? []);

    // How many statements an area shows before the rest go behind "N more".
    // Eight tickboxes at once reads as a form; four reads as a question.
    $shown = 4;
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

        @if ($areas === [])
            <p class="experience-groups__empty">{{ $c->help('items') }}</p>
        @else
            {{-- An accordion per area they chose. Native <details>, so it
                 opens with no JavaScript and a screen reader already knows
                 what a disclosure is. The first one starts open so the screen
                 does not look empty. --}}
            <div class="experience-groups">
                @foreach ($areas as $i => $area)
                    @php
                        $meta = \App\Support\Taxonomy::category($area);
                        $items = \App\Support\Taxonomy::items($area);
                        $picked = $chosen[$area] ?? [];
                        $first = array_slice($items, 0, $shown, true);
                        $rest = array_slice($items, $shown, null, true);
                        // Anything already ticked has to be on screen, or a
                        // revisit looks like it lost their answer.
                        $restIsChosen = array_intersect(array_keys($rest), (array) $picked) !== [];
                    @endphp

                    <details class="experience-groups__item" @if ($i === 0) open @endif>
                        <summary class="experience-groups__summary">
                            {{ $meta['label'] }}
                            <span class="experience-groups__mark" aria-hidden="true"></span>
                        </summary>

                        <div class="experience-groups__body">
                            <p class="experience-groups__note">{{ $c->label('privacy_note') }}</p>

                            <x-register.check-list :name="'items['.$area.']'"
                                                   :options="$first"
                                                   :chosen="$picked"
                                                   :legend="$meta['label']" />

                            @if ($rest !== [])
                                <details class="experience-groups__more" @if ($restIsChosen) open @endif>
                                    <summary class="experience-groups__more-summary">
                                        {{ count($rest) }} more
                                    </summary>

                                    <x-register.check-list :name="'items['.$area.']'"
                                                           :options="$rest"
                                                           :chosen="$picked"
                                                           :legend="'More about '.$meta['label']" />
                                </details>
                            @endif
                        </div>
                    </details>
                @endforeach
            </div>
        @endif

        <div class="wizard__actions">
            <button class="button button--primary" type="submit">Continue</button>
        </div>
    </form>
</x-register.shell>
