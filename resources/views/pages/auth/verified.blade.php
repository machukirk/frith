@php
    $c = \App\Support\PageContent::for('auth');
    $v = $c['verified'];
    $founder = auth('founder')->user();
@endphp

<x-layouts.site :title="$v['title'].' · Frith'" :noindex="true">

    <section class="auth">
        <div class="auth__inner auth__inner--centred">
            <p class="auth__badge auth__badge--done" aria-hidden="true">&#10003;</p>

            <h1 class="heading auth__title">{{ $v['title'] }}</h1>
            <p class="auth__standfirst">{{ $v['standfirst'] }}</p>

            @unless ($founder?->isComplete())
                {{-- Linen with a eucalyptus rule, not the teal reassurance
                     box. This screen is already good news; the box under it
                     is a prompt to finish something, and in the same teal it
                     reads as a second congratulation. --}}
                <p class="note note--aside">
                    {{ $v['unfinished'] }}
                    <a href="{{ route('register.start') }}">{{ $v['unfinished_link'] }}</a>.
                </p>
            @endunless

            <div class="auth__foot auth__foot--action">
                <a class="button button--primary button--block" href="{{ route('home') }}">{{ $v['button'] }}</a>
            </div>
        </div>
    </section>

</x-layouts.site>
