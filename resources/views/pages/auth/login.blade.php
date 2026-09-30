@php
    $c = \App\Support\PageContent::for('auth');
    $l = $c['login'];
@endphp

<x-layouts.auth :title="$c['meta']['title']" :description="$c['meta']['description']">

    <section class="auth">
        <div class="auth__inner">
            <h1 class="heading auth__title">{{ $l['title'] }}</h1>
            <p class="auth__standfirst">{{ $l['standfirst'] }}</p>

            <form class="auth__form" action="{{ route('login.attempt') }}" method="POST">
                @csrf
                <x-honeypot />

                <div class="field">
                    <label class="field__label" for="email">{{ $l['email'] }}</label>
                    <input class="field__control" id="email" name="email" type="email"
                           value="{{ old('email') }}" autocomplete="email" required autofocus
                           @error('email') aria-invalid="true" aria-describedby="email-error" @enderror>
                    @error('email')<p class="field__error" id="email-error">{{ $message }}</p>@enderror
                </div>

                <div class="field">
                    <span class="auth__label-row">
                        <label class="field__label" for="password">{{ $l['password'] }}</label>
                        <a class="auth__aside-link" href="{{ route('forgot') }}">{{ $l['forgotten'] }}</a>
                    </span>
                    <input class="field__control" id="password" name="password" type="password"
                           autocomplete="current-password" required
                           @error('password') aria-invalid="true" aria-describedby="password-error" @enderror>
                    @error('password')<p class="field__error" id="password-error">{{ $message }}</p>@enderror
                </div>

                <button class="button button--primary button--block" type="submit">{{ $l['button'] }}</button>
            </form>

            <p class="auth__divider">{{ $l['divider'] }}</p>

            {{-- Its own form, so the password field's "required" does not stop
                 somebody who only wants a link. --}}
            <form action="{{ route('login.send-link') }}" method="POST">
                @csrf
                <x-honeypot />
                <input type="hidden" name="email" value="{{ old('email') }}">

                <button class="button button--quiet button--block" type="submit">{{ $l['link_button'] }}</button>
            </form>

            <p class="auth__note">{{ $l['note'] }}</p>

            <p class="auth__foot">
                {{ $l['no_account'] }}
                <a href="{{ route('register.start') }}">{{ $l['register'] }}</a>
            </p>
        </div>
    </section>

</x-layouts.auth>
