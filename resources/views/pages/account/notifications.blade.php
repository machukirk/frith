<x-account.shell
    heading="Notifications"
    :tag="$hasPlus ? 'Frith+' : null"
    :standfirst="$hasPlus
        ? 'You hear about everything, and you choose which. At most one email a day, and nothing at all between 9pm and 8am.'
        : 'Frith is quiet on the free plan. You will hear when someone replies to you, and when a Frith+ member asks to connect. Other requests wait for you in Requests.'"
    current="notifications"
>
    @unless ($hasPlus)
        {{-- Said plainly rather than left for somebody to discover: a request
             that is never emailed about will sit unseen, and the honest thing
             is to say where it will be waiting. --}}
        <div class="note note--standalone">
            <span class="note__label">What this means in practice</span>
            If another family on the free plan asks to connect, we will not email
            or notify you. Their request will be waiting in
            <a href="{{ route('requests') }}">Requests</a> when you next open Frith.
            It is worth looking in every week or so.
        </div>
    @endunless

    <form method="POST" action="{{ route('account.notifications.update') }}">
        @csrf

        <ul class="switch-list">
            @foreach ($switches->where('shown') as $switch)
                <li class="switch-list__item">
                    <label class="switch @if ($switch['locked']) switch--locked @endif">
                        <input class="switch__input" type="checkbox"
                               name="prefs[{{ $switch['key'] }}]" value="1"
                               @checked($switch['on'])
                               @disabled($switch['locked'])>
                        <span class="switch__mark" aria-hidden="true"></span>
                        <span class="switch__text">
                            <span class="switch__label">
                                {{ $switch['label'] }}

                                {{-- A disabled control is out of the tab order,
                                     so why it cannot be changed has to be in
                                     the label rather than only beside it. --}}
                                @if ($switch['locked'])
                                    <span class="visually-hidden">
                                        {{ $switch['plus']
                                            ? '(needs a Frith+ membership)'
                                            : '(always on, and cannot be turned off)' }}
                                    </span>
                                @endif
                            </span>
                            <span class="switch__blurb">{{ $switch['blurb'] }}</span>
                        </span>
                    </label>

                    @if ($switch['plus'] && ! $hasPlus)
                        <span class="switch-list__plus">Frith+</span>
                    @endif
                </li>
            @endforeach
        </ul>

        {{-- Pace only exists with Frith+, because on the free plan requests
             are not emailed at all and so there is no pace to set. --}}
        @if ($hasPlus)
            <fieldset class="pace">
                <legend class="pace__legend">How many requests you would like</legend>
                <p class="pace__note">
                    Some weeks you have the energy for this and some weeks you do
                    not. Change it whenever, and nobody is told.
                </p>

                <div class="pace__options">
                    @foreach ($paces as $value => $label)
                        <label class="pace__option">
                            <input class="pace__input" type="radio" name="pace" value="{{ $value }}"
                                   @checked(old('pace', \App\Support\NotificationSettings::pace($family)) === $value)>
                            <span class="pace__label">{{ $label }}</span>
                        </label>
                    @endforeach
                </div>
            </fieldset>
        @endif

        <ul class="switch-list switch-list--single">
            <li class="switch-list__item">
                <label class="switch">
                    <input class="switch__input" type="checkbox" name="quiet_hours" value="1"
                           @checked(old('quiet_hours', $family->quiet_hours))>
                    <span class="switch__mark" aria-hidden="true"></span>
                    <span class="switch__text">
                        <span class="switch__label">Quiet hours</span>
                        <span class="switch__blurb">Nothing between 9pm and 8am</span>
                    </span>
                </label>
            </li>
        </ul>

        <div class="account-form__actions">
            <button class="button button--primary" type="submit">Save</button>
        </div>
    </form>

    @if ($hasPlus)
        <p class="account__subnote">
            Whatever else is off, you will still hear when a Frith+ member asks to
            connect. It is the one we never switch off, on either plan, so that a
            request is never left sitting unseen.
        </p>

        <div class="note note--standalone">
            <strong>Your requests always reach people.</strong>
            When you ask a family to connect, they are notified whether they are on
            Frith+ or not. That is part of your membership.
        </div>
    @else
        <div class="panel panel--offer">
            <h2 class="panel__title">Frith+ turns notifications on</h2>
            <p class="panel__body">
                Every request, every reply, and control over which you want. Frith+
                members are also always notified when you ask them, so your requests
                are seen.
            </p>
            <p>
                <a class="button button--primary button--small" href="{{ route('frith-plus') }}">
                    Explore Frith+
                </a>
            </p>
        </div>
    @endif

    <p class="account__foot-note">
        We do not send streaks, reminders that you have been away, or counts of
        what you have missed, on either plan.
    </p>
</x-account.shell>
