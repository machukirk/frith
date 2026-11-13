@props(['name'])

{{-- Line icons, drawn at 24 and scaled by the class that uses them. Decorative
     everywhere they appear so far, so hidden from assistive tech. --}}
@php
    $paths = [
        'people' => '<circle cx="9" cy="8" r="3.2"/><path d="M3.5 19.5a5.8 5.8 0 0 1 11 0"/><circle cx="17" cy="9.5" r="2.4"/><path d="M15.6 15.2a4.6 4.6 0 0 1 5.4 4.3"/>',
        'leaf' => '<path d="M20 4c0 8.5-4.8 13-11.5 13H5.5C5.5 9.5 11 4 20 4Z"/><path d="M4 21c1.8-5 5-8.6 9.5-11"/>',
        'search' => '<circle cx="10.5" cy="10.5" r="6.5"/><path d="M15.5 15.5 20 20"/>',
        'star' => '<path d="M12 3l2.6 5.6 6 .8-4.4 4.2 1.1 6-5.3-3-5.3 3 1.1-6L3.4 9.4l6-.8Z"/>',
        'shield' => '<path d="M12 3.2 5 6v5.4c0 4.6 2.9 7.9 7 9.4 4.1-1.5 7-4.8 7-9.4V6l-7-2.8Z"/><path d="m9 12 2.2 2.2L15.5 10"/>',
        'heart' => '<path d="M12 20.3C7 16.8 3.8 14 3.8 10.3A4.4 4.4 0 0 1 12 7.9a4.4 4.4 0 0 1 8.2 2.4c0 3.7-3.2 6.5-8.2 10Z"/>',
        'speech' => '<path d="M4 6h16v10H9l-5 4V6Z"/>',
        'pin' => '<path d="M12 21s7-5.5 7-11a7 7 0 1 0-14 0c0 5.5 7 11 7 11Z"/><circle cx="12" cy="10" r="2.5"/>',
        'calendar' => '<rect x="4" y="5.5" width="16" height="14" rx="3"/><path d="M8 3.5v4M16 3.5v4M4 11h16"/>',
    ];
@endphp

<svg {{ $attributes }} viewBox="0 0 24 24" fill="none" stroke="currentColor"
     stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
    {!! $paths[$name] ?? '' !!}
</svg>
