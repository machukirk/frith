<x-layouts.site :title="$other->displayName().' · Frith'" :noindex="true">

    <section class="thread">
        <div class="thread__head">
            {{-- Not a link once they have gone: there is no profile at the
                 other end of it any more. --}}
            @if ($canReply)
                <a class="thread__who" href="{{ route('family', $other->public_id) }}">
                    <span class="family-card__avatar" aria-hidden="true">{{ $other->initial() }}</span>
                    <span>
                        <span class="family-card__name">{{ $other->displayName() }}</span>
                        <span class="family-card__meta">{{ $other->area() }}</span>
                    </span>
                </a>
            @else
                <div class="thread__who">
                    <span class="family-card__avatar" aria-hidden="true">&middot;</span>
                    <span>
                        <span class="family-card__name">{{ $other->displayName() }}</span>
                        <span class="family-card__meta">What was said is still here</span>
                    </span>
                </div>
            @endif

            <a class="thread__safety" href="{{ \App\Support\SiteNavigation::url('meet-up-safety') }}">Safety</a>
        </div>

        @if (session('status'))
            <p class="journey-list__status" role="status">{{ session('status') }}</p>
        @endif

        <ol class="thread__lines">
            @forelse ($messages as $message)
                @php $mine = $message->sender_id === $family->id; @endphp

                <li class="thread__line {{ $mine ? 'thread__line--mine' : '' }}">
                    <p class="thread__bubble">{{ $message->body }}</p>
                    <p class="thread__when">
                        <span class="visually-hidden">{{ $mine ? 'You' : $other->displayName() }}, </span>
                        {{ $message->created_at->diffForHumans() }}
                    </p>
                </li>
            @empty
                <li class="thread__empty">
                    You are connected. Whoever says the first thing, it does not have
                    to be clever.
                </li>
            @endforelse
        </ol>

        @unless ($canReply)
            <p class="note thread__note">
                This family has left Frith. You can read what was said, and nothing
                you wrote has gone anywhere, but there is nobody at the other end
                to read a reply now.
            </p>
        @endunless

        {{-- Guidelines §07: said where the worry is. --}}
        @if ($canReply)
        <p class="note thread__note">
            Meeting in person? Choose somewhere public and tell somebody where you
            are going. <a href="{{ \App\Support\SiteNavigation::url('meet-up-safety') }}">The meet-up safety guide</a>
            is two minutes long.
        </p>

        <form class="thread__form" method="POST" action="{{ route('messages.send', $connection) }}">
            @csrf
            <x-honeypot />

            <div class="field">
                <label class="field__label" for="body">Write to {{ $other->displayName() }}</label>
                <textarea class="field__control field__control--multiline" id="body" name="body"
                          rows="3" maxlength="2000" required
                          @error('body') aria-invalid="true" aria-describedby="body-error" @enderror
                >{{ old('body') }}</textarea>
                @error('body')<p class="field__error" id="body-error">{{ $message }}</p>@enderror
            </div>

            <button class="button button--primary" type="submit">Send</button>
        </form>
        @endif

        <p class="family-list__back"><a href="{{ route('messages') }}">&larr; All messages</a></p>
    </section>

</x-layouts.site>
