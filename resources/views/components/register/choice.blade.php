{{-- A checkbox or radio drawn as a full-width card. 48px minimum target,
     and the whole card is the label so there is no small tickbox to hit. --}}
@props([
    'name',
    'value',
    'label',
    'description' => null,
    'checked' => false,
    'type' => 'checkbox',
])

@php $id = 'choice-'.$name.'-'.$value; @endphp

<label class="choice" for="{{ $id }}">
    <input class="choice__input"
           id="{{ $id }}"
           type="{{ $type }}"
           name="{{ $name }}"
           value="{{ $value }}"
           @checked($checked)>
    <span class="choice__mark" aria-hidden="true"></span>
    <span class="choice__text">
        <span class="choice__label">{{ $label }}</span>
        @if ($description)
            <span class="choice__description">{{ $description }}</span>
        @endif
    </span>
</label>
