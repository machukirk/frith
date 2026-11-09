<x-account.shell
    heading="Leave Frith"
    standfirst="What happens, before it happens."
    current="data"
>
    <ul class="leave-list">
        <li class="leave-list__item">
            <span class="leave-list__when">Straight away</span>
            Your profile stops being visible. You leave every Journey, and any
            request waiting on you or on somebody else is closed, so nobody is left
            waiting on a family who has gone.
        </li>
        <li class="leave-list__item">
            <span class="leave-list__when">Within {{ $graceDays }} days</span>
            Your answers, your children's ages, your postcode area and your account
            are deleted.
        </li>
        <li class="leave-list__item leave-list__item--caveat">
            <span class="leave-list__when">Not removed</span>
            Messages you have already sent stay in the other family's inbox. We
            cannot take words out of somebody else's conversation, and we would
            rather say so than imply a clean exit we cannot give you. A blank
            record stays with those messages so they still make sense to read.
            It has your name off it, and everything else.
        </li>
    </ul>

    <div class="note note--standalone">
        If you would rather just be left alone for a while, you do not have to
        leave. Turning everything off in
        <a href="{{ route('account.notifications') }}">notifications</a> and leaving
        your Journeys makes Frith silent and keeps your answers, in case you want
        them later.
    </div>

    <form class="account-form" method="POST" action="{{ route('account.delete.store') }}">
        @csrf

        <div class="check-list">
            <label class="check-list__item">
                <input type="checkbox" name="understood" value="1"
                       @error('understood') aria-invalid="true" @enderror>
                <span class="check-list__mark" aria-hidden="true"></span>
                <span class="check-list__label">
                    I have read what happens, and I would like to leave.
                </span>
            </label>
        </div>

        @error('understood')<p class="field__error">{{ $message }}</p>@enderror

        <div class="account-form__actions">
            <button class="button button--primary" type="submit">Delete my account</button>
            <a class="account-form__cancel" href="{{ route('account.data') }}">Keep my account</a>
        </div>
    </form>
</x-account.shell>
