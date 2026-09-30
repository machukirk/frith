@props(['name'])

{{-- Line icons, drawn at 24 and scaled by the class that uses them. Decorative
     everywhere they appear so far, so hidden from assistive tech. --}}
@php
    $paths = [
        'people' => '<circle cx="9" cy="8" r="3.2"/><path d="M3.5 19.5a5.8 5.8 0 0 1 11 0"/><circle cx="17" cy="9.5" r="2.4"/><path d="M15.6 15.2a4.6 4.6 0 0 1 5.4 4.3"/>',
        'leaf' => '<path d="M20 4c0 8.5-4.8 13-11.5 13H5.5C5.5 9.5 11 4 20 4Z"/><path d="M4 21c1.8-5 5-8.6 9.5-11"/>',
        'shield' => '<path d="M12 3.2 5 6v5.4c0 4.6 2.9 7.9 7 9.4 4.1-1.5 7-4.8 7-9.4V6l-7-2.8Z"/><path d="m9 12 2.2 2.2L15.5 10"/>',
    ];
@endphp

<svg {{ $attributes }} viewBox="0 0 24 24" fill="none" stroke="currentColor"
     stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
    {!! $paths[$name] ?? '' !!}
</svg>
