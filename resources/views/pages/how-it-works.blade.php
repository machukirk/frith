@php
    $c = \App\Support\PageContent::for('how-it-works');
@endphp

<x-layouts.site :title="$c['meta']['title']" :description="$c['meta']['description']" slug="how-it-works">

    {{-- Page head ------------------------------------------------------- --}}
    <section class="page-head">
        <div class="page-head__inner">
            <p class="eyebrow">{{ $c['head']['eyebrow'] }}</p>
            <h1 class="heading page-head__title">{{ $c['head']['title'] }}</h1>
            <p class="page-head__standfirst">{{ $c['head']['standfirst'] }}</p>
        </div>
    </section>

    {{-- The six steps ---------------------------------------------------- --}}
    <section class="band band--alt">
        <div class="wrap">
            <ol class="step-list">
                @foreach ($c['steps'] as $step)
                    <li class="step-list__item">
                        <span class="step-list__number" aria-hidden="true">{{ $loop->iteration }}</span>

                        <div>
                            <p class="step-list__title">{{ $step['title'] }}</p>
                            <p class="step-list__body">{{ $step['body'] }}</p>

                            @if (filled($step['note'] ?? null))
                                <p class="note step-list__note">{{ $step['note'] }}</p>
                            @endif
                        </div>
                    </li>
                @endforeach
            </ol>
        </div>
    </section>

    {{-- Call to action --------------------------------------------------- --}}
    <section class="band band--inverse band--tight cta-band">
        <div class="cta-band__inner">
            <div>
                <h2 class="cta-band__title">{{ $c['cta']['title'] }}</h2>
                <p class="cta-band__body">{{ $c['cta']['body'] }}</p>
            </div>
            <a class="button button--accent" href="{{ route('register.start') }}">{{ $c['cta']['button'] }}</a>
        </div>
    </section>

</x-layouts.site>
