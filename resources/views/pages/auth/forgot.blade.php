@php
    $c = \App\Support\PageContent::for('auth');
    $f = $c['forgot'];
@endphp

<x-layouts.site :title="$f['title'].' — Frith'" :noindex="true">

    <section class="auth">
        <div class="auth__inner">
            <h1 class="heading auth__title">{{ $f['title'] }}</h1>
            <p class="auth__standfirst">{{ $f['standfirst'] }}</p>

            <form class="auth__form" action="{{ route('login.send-link') }}" method="POST">
                @csrf
                <x-honeypot />

                <div class="field">
                    <label class="field__label" for="email">{{ $f['email'] }}</label>
                    <input class="field__control" id="email" name="email" type="email"
                           value="{{ old('email') }}" autocomplete="email" required autofocus
                           @error('email') aria-invalid="true" aria-describedby="email-error" @enderror>
                    @error('email')<p class="field__error" id="email-error">{{ $message }}</p>@enderror
                </div>

                <button class="button button--primary button--block" type="submit">{{ $f['button'] }}</button>
            </form>

            <p class="auth__foot">
                <a href="{{ route('login') }}">{{ $f['back'] }}</a>
            </p>
        </div>
    </section>

</x-layouts.site>
