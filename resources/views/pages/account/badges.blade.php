<x-account.shell
    heading="Badges"
    standfirst="Small markers that tell another family something useful about you. There is no score, no leaderboard, and nothing expires."
    current="badges"
>
    <div class="note note--standalone">
        You can hide any badge from your profile. Nobody is told what you have
        or have not got, and we will never nudge you to collect one.
    </div>

    <form method="POST" action="{{ route('account.badges.update') }}">
        @csrf

        @foreach ($groups as $key => $group)
            @php $inGroup = $badges[$key] ?? collect(); @endphp

            @if ($inGroup->isNotEmpty())
                <section class="badge-group">
                    <h2 class="badge-group__title">{{ $group['title'] }}</h2>
                    <p class="badge-group__blurb">{{ $group['blurb'] }}</p>

                    <ul class="badge-list">
                        @foreach ($inGroup as $badge)
                            <li class="badge-list__item @unless ($badge['held']) badge-list__item--not-yet @endunless">
                                <div class="badge-list__head">
                                    <p class="badge-list__title">{{ $badge['title'] }}</p>

                                    @if ($badge['held'])
                                        <span class="badge-list__state badge-list__state--held">Yours</span>
                                    @else
                                        <span class="badge-list__state">Not yet</span>
                                    @endif
                                </div>

                                <p class="badge-list__what">{{ $badge['what'] }}</p>
                                <p class="badge-list__note">{{ $badge['note'] }}</p>

                                {{-- The Helping hand is a choice rather than
                                     something that happened, so it is the one
                                     with a switch of its own. --}}
                                @if ($badge['choice'])
                                    <label class="switch">
                                        <input class="switch__input" type="checkbox" name="helping_hand" value="1"
                                               @checked(old('helping_hand', $family->helping_hand))>
                                        <span class="switch__mark" aria-hidden="true"></span>
                                        <span class="switch__label">Families can ask me for advice</span>
                                    </label>
                                @endif

                                @if ($badge['held'])
                                    <label class="switch switch--quiet">
                                        <input class="switch__input" type="checkbox" name="shown[]" value="{{ $badge['slug'] }}"
                                               @checked(! $badge['hidden'])>
                                        <span class="switch__mark" aria-hidden="true"></span>
                                        <span class="switch__label">Show this on my profile</span>
                                    </label>
                                @endif
                            </li>
                        @endforeach
                    </ul>
                </section>
            @endif
        @endforeach

        <div class="account-form__actions">
            <button class="button button--primary" type="submit">Save</button>
        </div>
    </form>

    <p class="account__foot-note">
        Badges are descriptive, not rewards. They exist so a family deciding
        whether to reach out has a little more to go on, not to encourage you to
        use Frith more.
    </p>
</x-account.shell>
