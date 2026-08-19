@props([
    'name',
    'label',
    'value' => null,
    'type' => 'text',
    'help' => null,
    'required' => false,
    'inputmode' => null,
    'autocomplete' => null,
    'placeholder' => null,
])

@php $id = 'field-'.$name; @endphp

<div class="field">
    <label class="field__label" for="{{ $id }}">
        {{ $label }}
        @unless ($required)<span class="field__optional">Optional</span>@endunless
    </label>

    <input class="field__input"
           id="{{ $id }}"
           name="{{ $name }}"
           type="{{ $type }}"
           value="{{ $value }}"
           @if ($inputmode) inputmode="{{ $inputmode }}" @endif
           @if ($autocomplete) autocomplete="{{ $autocomplete }}" @endif
           @if ($placeholder) placeholder="{{ $placeholder }}" @endif
           @if ($help) aria-describedby="{{ $id }}-help" @endif
           @error($name) aria-invalid="true" @enderror
           {{ $attributes }}>

    @if ($help)
        <p class="field__help" id="{{ $id }}-help">{{ $help }}</p>
    @endif

    <div aria-live="polite">
        @error($name)
            <p class="alert alert--error">
                <span class="alert__mark" aria-hidden="true">&#10005;</span>
                <span>{{ $message }}</span>
            </p>
        @enderror
    </div>
</div>
