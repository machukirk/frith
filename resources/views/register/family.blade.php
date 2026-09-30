@php
    $c = \App\Support\StepContent::for('family');
    $chosen = old('family_structures', session('registration.family_structures', $registration?->family_structures ?? []));
    $rows = old('children', $children);
    $months = ['January','February','March','April','May','June','July','August','September','October','November','December'];
    $thisYear = (int) now()->year;
@endphp

<x-register.shell
    :heading="$c->heading()"
    :standfirst="$c->standfirst()"
    :step="$stepNumber" :total="$totalSteps" :previous="$previous"
>
    <form class="wizard__form" method="POST" action="{{ route('register.step.store', $step) }}">
        @csrf
        <x-honeypot />

        <div class="wizard__group">
            <p class="wizard__label">{{ $c->label('family_structures', 'Your household') }}</p>
            <x-register.pills name="family_structures"
                              :options="\App\Support\Taxonomy::familyStructures()"
                              :chosen="$chosen"
                              legend="Your household. Choose as many as fit." />
            <p class="wizard__hint">{{ $c->help('family_structures') }}</p>
        </div>

        <div class="wizard__group">
            <p class="wizard__label">{{ $c->label('children', 'Your children') }}</p>
            <p class="wizard__hint wizard__hint--leading">{{ $c->help('children') }}</p>

            <div class="child-rows">
                @foreach ($rows as $i => $row)
                    <fieldset class="child-rows__item">
                        {{-- Directly inside the fieldset, so it is this group's
                             accessible name rather than a stray heading. --}}
                        <legend class="child-rows__label">Child {{ $i + 1 }}</legend>

                        @if (count($rows) > 1)
                            <div class="child-rows__head">
                                <button class="child-rows__remove" type="submit" name="action" value="remove:{{ $i }}">
                                    Remove<span class="visually-hidden"> child {{ $i + 1 }}</span>
                                </button>
                            </div>
                        @endif

                        <div class="child-rows__fields">
                            <span class="field">
                                <label class="field__label" for="child-{{ $i }}-month">Month</label>
                                <select class="select" id="child-{{ $i }}-month" name="children[{{ $i }}][birth_month]">
                                    <option value="">—</option>
                                    @foreach ($months as $m => $name)
                                        <option value="{{ $m + 1 }}" @selected((int) ($row['birth_month'] ?? 0) === $m + 1)>{{ $name }}</option>
                                    @endforeach
                                </select>
                            </span>

                            <span class="field">
                                <label class="field__label" for="child-{{ $i }}-year">Year</label>
                                <select class="select" id="child-{{ $i }}-year" name="children[{{ $i }}][birth_year]">
                                    <option value="">—</option>
                                    @for ($y = $thisYear; $y >= $thisYear - 25; $y--)
                                        <option value="{{ $y }}" @selected((int) ($row['birth_year'] ?? 0) === $y)>{{ $y }}</option>
                                    @endfor
                                </select>
                            </span>
                        </div>
                    </fieldset>
                @endforeach
            </div>

            @error('children.*.birth_year')<p class="field__error">{{ $message }}</p>@enderror

            {{-- A submit, not a script: the screen works with no JavaScript. --}}
            <p>
                <button class="button button--quiet button--small" type="submit" name="action" value="add">
                    <span aria-hidden="true">&plus;</span> Add another child
                </button>
            </p>
        </div>

        <div class="wizard__actions">
            <button class="button button--primary" type="submit" name="action" value="continue">Continue</button>
        </div>
    </form>
</x-register.shell>
