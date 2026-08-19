@php
    $ink = '#1B2940';
    $inkSecondary = '#4F5B70';
    $font = "'Poppins', -apple-system, 'Segoe UI', Roboto, Helvetica, Arial, sans-serif";
@endphp

<x-mail.layout
    title="Frith — mail path check"
    preview="If this arrived, the mail path works."
    footnote="Sent by frith:test-email. Nobody receives this except whoever ran the command."
    unsubscribe-url="#"
>
    <h1 style="margin:0 0 16px 0; font-family:{{ $font }}; font-size:24px; line-height:1.3; font-weight:600; color:{{ $ink }};">
        The mail path works
    </h1>

    <p style="margin:0; font-family:{{ $font }}; font-size:16px; line-height:1.6; color:{{ $inkSecondary }};">
        This came from the Frith server, through the configured mailer, using the real
        layout and the real from address. If the logo above rendered, that resolves too.
    </p>
</x-mail.layout>
