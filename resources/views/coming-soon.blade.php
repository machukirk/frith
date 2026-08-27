@php
    // Edited in the admin panel, falling back to config/frith.php for anything
    // that has never been filled in. See App\Support\PageContent.
    $copy = \App\Support\PageContent::for('coming-soon');
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

                {{-- The sign-up is now a full registration at /join. It is a link
                     rather than a form so the first thing a visitor does is read,
                     not type — and so the wizard owns all of the validation. --}}
                <div class="hero__cta">
                    <a class="btn btn--hero" href="{{ route('register.start') }}">
                        {{ $copy['cta']['button'] }}
                    </a>
                    <p class="hero__cta-note">{{ $copy['cta']['note'] ?? '' }}</p>
                </div>

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

                    {{-- The tagline is part of the artwork here rather than text
                         beside it, so it is not editable in the admin panel — the
                         lockup is the lockup. --}}
                    <img class="status-band__lockup"
                         src="{{ asset('brand/logo/frith-logo-tagline-reversed.svg') }}"
                         alt="Frith — Find your village" width="1132" height="464">
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
