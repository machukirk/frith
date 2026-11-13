@php
    $c = \App\Support\PageContent::for('auth');
    $s = $c['link_sent'];
    $email = session('link-sent.email');
@endphp

<x-layouts.site :title="$s['title'].' · Frith'" :noindex="true">

    <section class="auth">
        <div class="auth__inner auth__inner--centred">
            <p class="auth__badge" aria-hidden="true">&#9993;</p>

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

            {{-- The one action on this screen is to send another, not to go
                 back: somebody here is waiting on an email. Posting their own
                 address back re-flashes it, so a second press still works,
                 and the route's own throttle still holds.

                 Somebody who landed here directly has no address in the
                 session and nothing to resend, so that case keeps the way
                 back to the form. --}}
            <div class="auth__foot auth__foot--action">
                @if ($email)
                    <form action="{{ route('login.send-link') }}" method="POST">
                        @csrf
                        <x-honeypot />
                        <input type="hidden" name="email" value="{{ $email }}">
                        {{-- The label belongs in config/content/auth.php with
                             the rest of this screen's wording. That file is
                             not this change's to write, so it falls back
                             until the key lands. --}}
                        <button class="button button--outline button--block" type="submit">
                            {{ $s['resend'] ?? 'Send it again' }}
                        </button>
                    </form>
                @else
                    <a class="button button--outline button--block" href="{{ route('login') }}">{{ $s['back'] }}</a>
                @endif
            </div>
        </div>
    </section>

</x-layouts.site>
