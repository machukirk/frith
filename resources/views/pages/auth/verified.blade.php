@php
    $c = \App\Support\PageContent::for('auth');
    $v = $c['verified'];
    $founder = auth('founder')->user();
@endphp

<x-layouts.site :title="$v['title'].' — Frith'" :noindex="true">

    <section class="auth">
        <div class="auth__inner">
            <h1 class="heading auth__title">{{ $v['title'] }}</h1>
            <p class="auth__standfirst">{{ $v['standfirst'] }}</p>

            @unless ($founder?->isComplete())
                <p class="note">
                    {{ $v['unfinished'] }}
                    <a href="{{ route('register.start') }}">{{ $v['unfinished_link'] }}</a>.
                </p>
            @endunless

            <p class="auth__foot">
                <a class="button button--primary" href="{{ route('home') }}">{{ $v['button'] }}</a>
            </p>
        </div>
    </section>

</x-layouts.site>
