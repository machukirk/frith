@props(['name', 'options', 'chosen' => [], 'legend' => null, 'variant' => null])

<fieldset class="check-list {{ $variant ? 'check-list--'.$variant : '' }}">
    @if ($legend)<legend class="visually-hidden">{{ $legend }}</legend>@endif

    @foreach ($options as $slug => $label)
        <label class="check-list__item">
            <input type="checkbox" name="{{ $name }}[]" value="{{ $slug }}"
                   @checked(in_array($slug, (array) $chosen, true))>
            <span class="check-list__mark" aria-hidden="true"></span>
            <span class="check-list__label">{{ $label }}</span>
        </label>
    @endforeach
</fieldset>
