{{--
    Icons per brand guidelines §05: 24×24 grid, 1.5 stroke at 24px, round caps
    and joins, Eucalyptus 700, at most one coral element and only as a filled
    dot. Decorative here — the heading beside each one carries the meaning.
--}}
@props(['name', 'size' => 30])

<svg viewBox="0 0 24 24" width="{{ $size }}" height="{{ $size }}"
     fill="none" stroke="#274B44" stroke-width="1.5"
     stroke-linecap="round" stroke-linejoin="round"
     aria-hidden="true" focusable="false" {{ $attributes }}>
    @switch($name)
        @case('people')
            <circle cx="9" cy="8" r="3.2" />
            <path d="M3.5 19.5c0-3 2.5-5 5.5-5s5.5 2 5.5 5" />
            <circle cx="17" cy="9.5" r="2.4" />
            <path d="M17 14.4c2.2 0 3.8 1.6 3.8 3.9" />
            @break
        @case('leaf')
            <path d="M20 4.5c0 7-4.6 11.2-9.8 11.2-2.6 0-4.6-1-5.9-2.5C7 6.7 12.8 4.2 20 4.5Z" />
            <path d="M4 20c1.6-4.4 4.8-7.6 9-9.4" />
            @break
        @case('heart')
            <path d="M12 20.3C7 16.8 3.8 14 3.8 10.3A4.4 4.4 0 0 1 12 7.9a4.4 4.4 0 0 1 8.2 2.4c0 3.7-3.2 6.5-8.2 10Z" />
            <circle cx="15.4" cy="11" r="1.6" fill="#E56A4E" stroke="none" />
            @break
    @endswitch
</svg>
