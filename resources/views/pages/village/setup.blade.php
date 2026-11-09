<x-layouts.site title="Before you go in — Frith" :noindex="true">

    <section class="auth">
        <div class="auth__inner">
            <h1 class="heading auth__title">One thing first</h1>
            <p class="auth__standfirst">
                What should other families call you? It is the only name they see,
                and you can change it whenever you like.
            </p>

            <form class="auth__form" action="{{ route('village.setup.store') }}" method="POST">
                @csrf

                <div class="field">
                    <label class="field__label" for="display_name">The name other families see</label>
                    <input class="field__control" id="display_name" name="display_name" type="text"
                           value="{{ old('display_name', $family->first_name) }}"
                           maxlength="60" required autofocus
                           @error('display_name') aria-invalid="true" aria-describedby="display_name-error" @enderror>
                    <p class="field__help" id="display_name-help">
                        Not your legal name unless you want it to be. A first name, a
                        surname, or whatever your family goes by.
                    </p>
                    @error('display_name')<p class="field__error" id="display_name-error">{{ $message }}</p>@enderror
                </div>

                <button class="button button--primary button--block" type="submit">Go to your village</button>
            </form>

            {{-- Said here because this is the moment somebody wonders. --}}
            <div class="note auth__note">
                Other families see this name, your rough area, and your children's
                ages. Everything you told us about what you are navigating stays
                private, and is only ever used to find your matches.
            </div>
        </div>
    </section>

</x-layouts.site>
