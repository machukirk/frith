@php
    $ink = '#1B2940';
    $inkSecondary = '#4F5B70';
    $euc = '#274B44';
    $font = "'Poppins', -apple-system, 'Segoe UI', Roboto, Helvetica, Arial, sans-serif";
@endphp

<x-mail.layout
    title="You’re already on the Frith waiting list"
    preview="Nothing to do — you’re already on the list."
    footnote="You’re getting this because someone entered this address on frith.community and it was already on the list."
    :unsubscribe-url="$unsubscribeUrl"
>

    <h1 style="margin:0 0 16px 0; font-family:{{ $font }}; font-size:24px; line-height:1.3; font-weight:600; color:{{ $ink }};">
        You’re already on the list
    </h1>

    <p style="margin:0 0 16px 0; font-family:{{ $font }}; font-size:16px; line-height:1.6; color:{{ $inkSecondary }};">
        Someone just entered this address on our sign-up page. It was already there, so there’s nothing for you to do — we’ll email you when Frith launches.
    </p>

    <p style="margin:0; font-family:{{ $font }}; font-size:16px; line-height:1.6; color:{{ $inkSecondary }};">
        If you’d rather not hear from us, the unsubscribe link below takes you off in one tap. Any questions, write to
        <a href="mailto:{{ config('frith.company.contact_email') }}" style="color:{{ $euc }};">{{ config('frith.company.contact_email') }}</a>
        and a person will answer.
    </p>

</x-mail.layout>
