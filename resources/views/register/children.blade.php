@php
    $months = [
        1 => 'January', 2 => 'February', 3 => 'March', 4 => 'April',
        5 => 'May', 6 => 'June', 7 => 'July', 8 => 'August',
        9 => 'September', 10 => 'October', 11 => 'November', 12 => 'December',
    ];
    $thisYear = (int) now()->year;
@endphp

<x-register.shell
    heading="How many children, and how old are they?"
    standfirst="Month and year is all we ask. It keeps their age right without us holding a date of birth."
    :step="$stepNumber" :total="$totalSteps" :previous="$previous"
>
    <form method="POST" action="{{ route('register.step.store', $step) }}">
        @csrf

        <div class="children" data-children>
            @foreach ($children as $i => $child)
                <fieldset class="child" data-child-row>
                    <legend class="child__legend">Child {{ $i + 1 }}</legend>

                    <div class="child__fields">
                        <div class="field">
                            <label class="field__label" for="child-{{ $i }}-month">Born</label>
                            <select class="field__input" id="child-{{ $i }}-month" name="children[{{ $i }}][birth_month]">
                                <option value="">Month</option>
                                @foreach ($months as $number => $name)
                                    <option value="{{ $number }}" @selected((int) old("children.$i.birth_month", $child['birth_month']) === $number)>{{ $name }}</option>
                                @endforeach
                            </select>
                        </div>

                        <div class="field">
                            <label class="field__label" for="child-{{ $i }}-year">Year</label>
                            <select class="field__input" id="child-{{ $i }}-year" name="children[{{ $i }}][birth_year]">
                                <option value="">Year</option>
                                @for ($y = $thisYear; $y >= $thisYear - 25; $y--)
                                    <option value="{{ $y }}" @selected((int) old("children.$i.birth_year", $child['birth_year']) === $y)>{{ $y }}</option>
                                @endfor
                            </select>
                        </div>

                        @if (count($children) > 1)
                            {{-- A submit, not a script, so the row can be removed with no JavaScript. --}}
                            <button class="btn btn--quiet child__remove" type="submit" name="action" value="remove:{{ $i }}">
                                Remove<span class="visually-hidden"> child {{ $i + 1 }}</span>
                            </button>
                        @endif
                    </div>

                    <div aria-live="polite">
                        @error("children.$i.birth_month")<p class="alert alert--error"><span class="alert__mark" aria-hidden="true">&#10005;</span><span>{{ $message }}</span></p>@enderror
                        @error("children.$i.birth_year")<p class="alert alert--error"><span class="alert__mark" aria-hidden="true">&#10005;</span><span>{{ $message }}</span></p>@enderror
                    </div>
                </fieldset>
            @endforeach
        </div>

        <button class="btn btn--quiet" type="submit" name="action" value="add" data-add-child>
            + Add another child
        </button>

        <div class="wizard__actions">
            <button class="btn" type="submit" name="action" value="continue">Continue</button>
        </div>
    </form>
</x-register.shell>
