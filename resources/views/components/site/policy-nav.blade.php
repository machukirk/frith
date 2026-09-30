@props(['current' => null])

{{-- The list of policies, shown beside each of them. On a phone it comes first
     and reads as a contents page, which is what it is. --}}
<aside class="policy__aside">
    <p class="policy__label">Policies</p>

    <nav class="policy__links" aria-label="Policies">
        @foreach (\App\Support\PolicyPages::all($current) as $page)
            <a class="policy__link"
               href="{{ $page['url'] }}"
               @if ($page['current']) aria-current="page" @endif>{{ $page['label'] }}</a>
        @endforeach
    </nav>

    <div class="policy__help">
        <p class="policy__help-title">Need a person?</p>
        <p class="policy__help-body">
            Write to
            <a href="mailto:{{ config('frith.company.contact_email') }}">{{ config('frith.company.contact_email') }}</a>
            and a real person will reply.
        </p>
    </div>
</aside>
