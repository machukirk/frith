@props(['name', 'options', 'chosen' => [], 'legend' => null])

<fieldset class="pills">
    @if ($legend)<legend class="visually-hidden">{{ $legend }}</legend>@endif

    @foreach ($options as $slug => $label)
        <label class="pills__item">
            <input type="checkbox" name="{{ $name }}[]" value="{{ $slug }}"
                   @checked(in_array($slug, (array) $chosen, true))>
            <span>{{ $label }}</span>
        </label>
    @endforeach
</fieldset>
