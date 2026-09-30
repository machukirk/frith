@php
    $ink = '#1B2940';
    $inkSecondary = '#4F5B70';
    $linen = '#F8F4EE';
    $euc = '#274B44';
    $font = "'Livvic', -apple-system, 'Segoe UI', Roboto, Helvetica, Arial, sans-serif";
@endphp

<x-mail.layout
    title="Your link to log in to Frith"
    preview="One tap and you are in. The link works once, for the next hour."
    footnote="You are getting this because somebody asked for a link to log in to {{ config('frith.company.contact_email') }}'s Frith account. If that was not you, ignore this email — nobody can get in without the link, and it expires on its own."
    :unsubscribe-url="route('home')"
>

    <h1 style="margin:0 0 16px 0; font-family:{{ $font }}; font-size:24px; line-height:1.3; font-weight:600; color:{{ $ink }};">
        Hello{{ $registration->first_name ? ', '.$registration->first_name : '' }}.
    </h1>

    <p style="margin:0 0 24px 0; font-family:{{ $font }}; font-size:16px; line-height:1.6; color:{{ $inkSecondary }};">
        Tap below to log in. No password needed.
    </p>

    {{-- Table-based button: Outlook ignores padding on an anchor. --}}
    <table role="presentation" cellpadding="0" cellspacing="0" border="0" style="margin:0 0 24px 0;">
        <tr>
            <td align="center" bgcolor="{{ $euc }}" style="border-radius:999px;">
                <a href="{{ $url }}"
                   style="display:inline-block; padding:15px 28px; font-family:{{ $font }}; font-size:16px; font-weight:500; line-height:1.2; color:{{ $linen }}; text-decoration:none; border-radius:999px;">
                    Log in to Frith
                </a>
            </td>
        </tr>
    </table>

    <p style="margin:0 0 24px 0; font-family:{{ $font }}; font-size:15px; line-height:1.6; color:{{ $inkSecondary }};">
        It works once, and for the next {{ $minutes }} minutes. If it has expired, ask for another from the log in page.
    </p>

    <p style="margin:0 0 8px 0; font-family:{{ $font }}; font-size:14px; line-height:1.6; color:{{ $inkSecondary }};">
        If the button doesn’t work, copy this into your browser:
    </p>
    <p style="margin:0; font-family:{{ $font }}; font-size:14px; line-height:1.6; word-break:break-all;">
        <a href="{{ $url }}" style="color:{{ $euc }};">{{ $url }}</a>
    </p>

</x-mail.layout>
