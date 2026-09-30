@php
    $c = \App\Support\PageContent::for('auth');
    $s = $c['link_sent'];
    $email = session('link-sent.email');
@endphp

<x-layouts.site :title="$s['title'].' — Frith'" :noindex="true">

    <section class="auth">
        <div class="auth__inner">
            <h1 class="heading auth__title">{{ $s['title'] }}</h1>

            {{-- Their own address back, if they came straight from the form.
                 Landing here directly gets the wording that says nothing about
                 whether anybody is registered. --}}
            <p class="auth__standfirst">
                @if ($email)
                    {{ str_replace(':email', $email, $s['standfirst']) }}
                @else
                    {{ $s['standfirst_generic'] }}
                @endif
            </p>

            <p class="note note--urgent">
                {!! str_replace(
                    ':email',
                    '<a href="mailto:'.e(config('frith.company.contact_email')).'">'.e(config('frith.company.contact_email')).'</a>',
                    e($s['trouble']),
                ) !!}
            </p>

            <p class="auth__foot">
                <a href="{{ route('login') }}">{{ $s['back'] }}</a>
            </p>
        </div>
    </section>

</x-layouts.site>
