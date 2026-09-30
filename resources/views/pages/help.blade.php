@php
    $c = \App\Support\PageContent::for('help');
    $f = $c['contact']['form'];
@endphp

<x-layouts.site :title="$c['meta']['title']" :description="$c['meta']['description']" slug="help">

    {{-- Page head ------------------------------------------------------------ --}}
    <section class="page-head">
        <div class="page-head__inner">
            <p class="eyebrow">{{ $c['head']['eyebrow'] }}</p>
            <h1 class="heading page-head__title">{{ $c['head']['title'] }}</h1>
            <p class="page-head__standfirst">{{ $c['head']['standfirst'] }}</p>

            {{-- A GET, so a result page can be linked to, and server-side, so
                 it works with no JavaScript and searches whatever an editor
                 last saved rather than a stale index. --}}
            <form class="search" action="{{ route('help') }}" method="GET" role="search">
                <svg class="search__icon" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                     stroke-width="2" stroke-linecap="round" aria-hidden="true">
                    <circle cx="11" cy="11" r="7"/><path d="m20 20-3.5-3.5"/>
                </svg>

                <label class="visually-hidden" for="help-search">{{ $c['head']['search_label'] }}</label>
                <input class="search__input"
                       id="help-search"
                       type="search"
                       name="q"
                       value="{{ request('q') }}"
                       placeholder="{{ $c['head']['search_placeholder'] }}">

                <button class="button button--primary button--small search__submit" type="submit">
                    {{ $c['head']['search_label'] }}
                </button>
            </form>
        </div>
    </section>

    {{-- Results, when somebody has searched ---------------------------------- --}}
    @php $results = \App\Support\HelpSearch::run(request('q')); @endphp

    @if (filled(request('q')))
        <section class="band band--page band--tight" aria-label="Search results">
            <div class="wrap search-results">
                @if ($results->isEmpty())
                    <p class="search-results__empty">
                        {{ str_replace(':query', request('q'), $c['search']['empty']) }}
                    </p>
                @else
                    <p class="search-results__summary">
                        {{ trans_choice($c['search']['found'], $results->count(), [
                            'count' => $results->count(),
                            'query' => request('q'),
                        ]) }}
                    </p>

                    <ul class="link-cards link-cards--tight">
                        @foreach ($results as $result)
                            <li class="link-cards__item">
                                <a class="link-cards__link" href="{{ $result['url'] }}">{{ $result['title'] }}</a>
                                <p class="link-cards__body">{{ $result['topic'] }}</p>
                            </li>
                        @endforeach
                    </ul>
                @endif
            </div>
        </section>
    @endif

    {{-- Topics --------------------------------------------------------------- --}}
    <section class="band band--page band--tight" aria-label="Help topics">
        <div class="wrap">
            <ul class="link-cards">
                @foreach ($c['topics'] as $topic)
                    <li class="link-cards__item">
                        <p class="link-cards__title">{{ $topic['title'] }}</p>

                        <div class="link-cards__links">
                            @foreach ($topic['links'] as $link)
                                <a class="link-cards__link" href="{{ \App\Support\SiteNavigation::url($link) }}">{{ $link['label'] }}</a>
                            @endforeach
                        </div>
                    </li>
                @endforeach
            </ul>
        </div>
    </section>

    {{-- Contact -------------------------------------------------------------- --}}
    <section class="band band--alt">
        <div class="wrap">
            <div class="section-head">
                <p class="eyebrow">{{ $c['contact']['eyebrow'] }}</p>
                <h2 class="heading heading--section section-head__title">{{ $c['contact']['title'] }}</h2>
                <p class="section-head__standfirst">{{ $c['contact']['standfirst'] }}</p>
            </div>

            <div class="split split--wide split--lead">
                <ul class="link-cards link-cards--tight">
                @foreach ($c['contact']['cards'] as $card)
                    @php $email = $card['email'] ?? config('frith.company.contact_email'); @endphp

                    <li class="link-cards__item">
                        <p class="link-cards__title">{{ $card['title'] }}</p>

                        <div class="link-cards__links">
                            <a class="link-cards__link" href="mailto:{{ $email }}">{{ $email }}</a>
                        </div>

                        <p class="link-cards__body">{{ $card['detail'] }}</p>
                    </li>
                @endforeach
                </ul>

                <div class="panel" id="contact">
                    <p class="panel__title">{{ $f['title'] }}</p>
                    <p class="panel__body">{{ $f['standfirst'] }}</p>

                    @if (session('contact.sent'))
                        <p class="note note--standalone" role="status">{{ $f['sent'] }}</p>
                    @else
                        <form class="contact-form" action="{{ route('help.contact') }}" method="POST">
                            @csrf
                            <x-honeypot />

                            <div class="field">
                                <label class="field__label" for="contact-name">{{ $f['name'] }}</label>
                                <input class="field__control" id="contact-name" name="name" type="text"
                                       value="{{ old('name') }}" autocomplete="name" required
                                       @error('name') aria-invalid="true" aria-describedby="contact-name-error" @enderror>
                                @error('name')<p class="field__error" id="contact-name-error">{{ $message }}</p>@enderror
                            </div>

                            <div class="field">
                                <label class="field__label" for="contact-email">{{ $f['email'] }}</label>
                                <input class="field__control" id="contact-email" name="email" type="email"
                                       value="{{ old('email') }}" autocomplete="email" required
                                       @error('email') aria-invalid="true" aria-describedby="contact-email-error" @enderror>
                                @error('email')<p class="field__error" id="contact-email-error">{{ $message }}</p>@enderror
                            </div>

                            {{-- Radios, not a select: four options are quicker to
                                 read than they are to open. --}}
                            <fieldset class="field">
                                <legend class="field__label">{{ $f['topic'] }}</legend>
                                <div class="topic-choices">
                                    @foreach ($f['topics'] as $i => $topic)
                                        <label class="topic-choices__item">
                                            <input type="radio" name="topic" value="{{ $topic['value'] }}"
                                                   @checked(old('topic', $f['topics'][0]['value']) === $topic['value'])>
                                            <span>{{ $topic['label'] }}</span>
                                        </label>
                                    @endforeach
                                </div>
                                @error('topic')<p class="field__error">{{ $message }}</p>@enderror
                            </fieldset>

                            <div class="field">
                                <label class="field__label" for="contact-message">{{ $f['message'] }}</label>
                                <textarea class="field__control field__control--multiline" id="contact-message"
                                          name="message" rows="5" required
                                          aria-describedby="contact-message-help"
                                          @error('message') aria-invalid="true" @enderror>{{ old('message') }}</textarea>
                                <p class="field__help" id="contact-message-help">{{ $f['message_help'] }}</p>
                                @error('message')<p class="field__error">{{ $message }}</p>@enderror
                            </div>

                            <div class="action-row action-row--bare">
                                <button class="button button--primary" type="submit">{{ $f['button'] }}</button>
                            </div>

                            <p class="field__help">{{ $f['reassurance'] }}</p>
                        </form>
                    @endif
                </div>
            </div>

            <p class="note note--panel note--urgent note--standalone">
                {!! str_replace(
                    ':number',
                    '<strong>'.e($c['contact']['emergency']['number']).'</strong>',
                    e($c['contact']['emergency']['body']),
                ) !!}
            </p>
        </div>
    </section>

</x-layouts.site>
