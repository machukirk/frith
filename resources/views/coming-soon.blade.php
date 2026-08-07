@php
    // Edited in the admin panel, falling back to config/frith.php for anything
    // that has never been filled in. See App\Support\PageContent.
    $copy = \App\Support\PageContent::for('coming-soon');
    $status = session('waitlist.status');
@endphp

<x-layouts.frith>

    <x-slot:badge>
        <span class="status-pill">{{ $copy['status_badge'] }}</span>
    </x-slot:badge>

    <main class="site-main gutter" id="main">

        <section class="hero">
            <div class="hero__copy">
                <p class="eyebrow">{{ $copy['eyebrow'] }}</p>

                <h1 class="hero__title">
                    @foreach ($copy['headline'] as $line)
                        {{ $line }}@if (! $loop->last)<br>@else<span class="full-stop">.</span>@endif
                    @endforeach
                </h1>

                <p class="hero__standfirst">{{ $copy['standfirst'] }}</p>

                @if ($status === 'pending-confirmation')

                    <div class="notice" role="status">
                        <p class="notice__heading"><span class="notice__mark" aria-hidden="true">&check;</span>{{ $copy['success']['heading'] }}</p>
                        <p class="notice__body">{{ $copy['success']['body'] }}</p>
                        <p class="notice__body">
                            {{ $copy['success']['footnote'] }}
                            <a href="mailto:{{ config('frith.company.contact_email') }}">{{ config('frith.company.contact_email') }}</a>
                        </p>
                    </div>

                @elseif ($status === 'confirmed')

                    <div class="notice" role="status">
                        <p class="notice__heading"><span class="notice__mark" aria-hidden="true">&check;</span>{{ $copy['confirmed']['heading'] }}</p>
                        <p class="notice__body">{{ $copy['confirmed']['body'] }}</p>
                    </div>

                @else

                    {{-- No JavaScript anywhere on this page. A plain POST and a
                         redirect works on a cracked phone, a slow connection and
                         a text-only browser alike. --}}
                    <form class="signup" method="POST" action="{{ route('waitlist.store') }}" novalidate>
                        @csrf
                        <x-honeypot />

                        <label class="signup__label" for="email">{{ $copy['form']['label'] }}</label>

                        <div class="signup__row">
                            <input class="signup__input"
                                   id="email"
                                   type="email"
                                   name="email"
                                   value="{{ old('email') }}"
                                   inputmode="email"
                                   autocomplete="email"
                                   autocapitalize="off"
                                   spellcheck="false"
                                   placeholder="{{ $copy['form']['placeholder'] }}"
                                   aria-describedby="email-help"
                                   @error('email') aria-invalid="true" @enderror
                                   >

                            <button class="btn" type="submit">{{ $copy['form']['button'] }}</button>
                        </div>

                        <p class="signup__help" id="email-help">{{ config('frith.consent.text') }}</p>

                        {{-- §08: errors announced politely, and carried by an
                             icon and words as well as colour. --}}
                        <div aria-live="polite">
                            @error('email')
                                <p class="alert alert--error">
                                    <span class="alert__mark" aria-hidden="true">&#10005;</span>
                                    <span>{{ $message }}</span>
                                </p>
                            @enderror
                        </div>
                    </form>

                @endif
            </div>

            {{-- The hero framing device (§05). Decorative, so the alt text
                 belongs to the photograph and the brush is hidden. --}}
            <div class="hero__figure">
                <div class="hero__photo-mask">
                    <img class="hero__photo"
                         src="{{ $copy['hero_image'] ? \Illuminate\Support\Facades\Storage::url($copy['hero_image']) : asset('brand/img/frith-hero-hillside.png') }}"
                         alt="{{ $copy['hero_image_alt'] }}"
                         width="519" height="340">
                </div>
                <img class="hero__brush" src="{{ asset('brand/img/frith-swoosh-hero.svg') }}" alt="" aria-hidden="true">
                <span class="hero__sun" aria-hidden="true"></span>
            </div>
        </section>

        <section class="cards" aria-label="What Frith does">
            @foreach ($copy['cards'] as $card)
                <div class="card">
                    <x-frith.icon :name="$card['icon']" class="card__icon" />
                    <h2 class="card__heading">{{ $card['heading'] }}</h2>
                    <p class="card__body">{{ $card['body'] }}</p>
                </div>
            @endforeach
        </section>

        <section class="status-band">
            <div class="status-band__grid">
                <div>
                    <h2 class="status-band__heading">{{ $copy['status']['heading'] }}</h2>
                    <p class="status-band__standfirst">{{ $copy['status']['standfirst'] }}</p>

                    <div class="status-band__lockup">
                        <img class="status-band__logo"
                             src="{{ asset('brand/logo/frith-logo-horizontal-reversed.svg') }}"
                             alt="Frith" width="490" height="155">
                        <p class="status-band__tagline">{{ $copy['status']['tagline'] }}</p>
                    </div>
                </div>

                <ul class="status-band__points">
                    @foreach ($copy['status']['points'] as $point)
                        <li><span><strong>{{ $point['lead'] }}</strong> {{ $point['rest'] }}</span></li>
                    @endforeach
                </ul>
            </div>
        </section>

        <aside class="note">
            <span class="note__mark" aria-hidden="true">i</span>
            <p class="note__body">{{ $copy['note'] }}</p>
        </aside>

    </main>

</x-layouts.frith>
