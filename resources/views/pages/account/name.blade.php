<x-account.shell
    heading="Name and area"
    standfirst="What other families see, and roughly where you are."
    current="profile"
>
    <form class="account-form" method="POST" action="{{ route('account.edit.store', 'name') }}">
        @csrf
        <x-honeypot />

        <div class="field">
            <label class="field__label" for="display_name">What should other families call you?</label>
            <input class="field__control" id="display_name" name="display_name" type="text"
                   value="{{ old('display_name', $registration->displayName()) }}"
                   maxlength="60" required aria-describedby="display_name-help"
                   @error('display_name') aria-invalid="true" @enderror>
            <p class="field__help" id="display_name-help">
                A first name, or whatever your family goes by. It does not have to be
                your real name.
            </p>
            @error('display_name')<p class="field__error">{{ $message }}</p>@enderror
        </div>

        <div class="field">
            <label class="field__label" for="postcode_outcode">The first part of your postcode</label>
            <input class="field__control field__control--short" id="postcode_outcode" name="postcode_outcode" type="text"
                   value="{{ old('postcode_outcode', $registration->postcode_outcode) }}"
                   placeholder="SS9" inputmode="text" autocomplete="postal-code" required
                   aria-describedby="postcode-help"
                   @error('postcode_outcode') aria-invalid="true" @enderror>
            <p class="field__help" id="postcode-help">
                Shown to other families as
                &ldquo;{{ \App\Support\Postcode::label(old('postcode_outcode', $registration->postcode_outcode)) ?: 'your area' }}&rdquo;.
                We never ask for the rest of it.
            </p>
            @error('postcode_outcode')<p class="field__error">{{ $message }}</p>@enderror
        </div>

        {{-- The email is the account. Changing it means proving the new one
             works, which is its own job and is not this screen. --}}
        <div class="field">
            <p class="field__label">Your email address</p>
            <p class="field__static">{{ $registration->email }}</p>
            <p class="field__help">
                This is how you log in, so it is not something we change from here.
                Write to {{ config('frith.company.contact_email') }} and a person will sort it.
            </p>
        </div>

        <div class="account-form__actions">
            <button class="button button--primary" type="submit">Save</button>
            <a class="account-form__cancel" href="{{ route('account') }}">Cancel</a>
        </div>
    </form>
</x-account.shell>
