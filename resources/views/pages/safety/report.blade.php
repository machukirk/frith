<x-layouts.site title="Report and block · Frith" :noindex="true">

    <section class="auth">
        <div class="auth__inner">
            <h1 class="heading auth__title">Report and block</h1>
            <p class="auth__standfirst">
                We review every report and take action, where needed.
                {{ $other->displayName() }} is blocked at the same time, and is never
                told either happened.
            </p>

            <form class="auth__form" method="POST" action="{{ route('report.store', $other->public_id) }}">
                @csrf
                <x-honeypot />

                <fieldset class="check-list">
                    <legend class="visually-hidden">What happened</legend>

                    @foreach ($reasons as $slug => $label)
                        <label class="check-list__item">
                            <input type="radio" name="reason" value="{{ $slug }}"
                                   @checked(old('reason') === $slug)>
                            <span class="check-list__mark" aria-hidden="true"></span>
                            <span class="check-list__label">{{ $label }}</span>
                        </label>
                    @endforeach
                </fieldset>

                @error('reason')<p class="field__error">{{ $message }}</p>@enderror

                <div class="field">
                    <label class="field__label" for="note">Anything you want to add</label>
                    <textarea class="field__control field__control--multiline" id="note" name="note"
                              rows="4" maxlength="2000"
                              @error('note') aria-invalid="true" @enderror
                    >{{ old('note') }}</textarea>
                    <p class="field__help">Optional. Only the safeguarding team reads it.</p>
                    @error('note')<p class="field__error">{{ $message }}</p>@enderror
                </div>

                {{-- Reporting and changing your mind on one row, inside the
                     form, so Cancel is beside the button rather than small
                     print under a full-width one.

                     The design fills this button with the deep red the brand
                     calls $coral-ink. button--danger is that fill, and the
                     label on it is 6.7:1. --}}
                <div class="safety-actions safety-actions--tight">
                    <button class="button button--danger" type="submit">Report and block</button>
                    <a class="safety-cancel safety-cancel--tight" href="{{ route('family', $other->public_id) }}">Cancel</a>
                </div>
            </form>
        </div>
    </section>

</x-layouts.site>
