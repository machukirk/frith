@php
    $ink = '#1B2940';
    $inkSecondary = '#4F5B70';
    $linen = '#F8F4EE';
    $euc = '#274B44';
    $font = "'Poppins', -apple-system, 'Segoe UI', Roboto, Helvetica, Arial, sans-serif";
@endphp

<x-mail.layout
    title="One tap to join the Frith waiting list"
    preview="Confirm your email and we’ll let you know when Frith launches."
    footnote="You’re getting this because someone entered this address on frith.community. If that wasn’t you, ignore this email and nothing happens — or unsubscribe below and we’ll take the address off."
    :unsubscribe-url="$unsubscribeUrl"
>

    <h1 style="margin:0 0 16px 0; font-family:{{ $font }}; font-size:24px; line-height:1.3; font-weight:600; color:{{ $ink }};">
        One tap and you’re on the list
    </h1>

    <p style="margin:0 0 24px 0; font-family:{{ $font }}; font-size:16px; line-height:1.6; color:{{ $inkSecondary }};">
        We just need to know this email is really yours. Tap the button and that’s it — there’s nothing else to fill in.
    </p>

    {{-- Table-based button: Outlook ignores padding on an anchor. --}}
    <table role="presentation" cellpadding="0" cellspacing="0" border="0" style="margin:0 0 24px 0;">
        <tr>
            <td align="center" bgcolor="{{ $euc }}" style="border-radius:999px;">
                <a href="{{ $confirmUrl }}"
                   style="display:inline-block; padding:15px 28px; font-family:{{ $font }}; font-size:16px; font-weight:500; line-height:1.2; color:{{ $linen }}; text-decoration:none; border-radius:999px;">
                    Yes, add me to the list
                </a>
            </td>
        </tr>
    </table>

    <p style="margin:0 0 8px 0; font-family:{{ $font }}; font-size:14px; line-height:1.6; color:{{ $inkSecondary }};">
        This link works for the next {{ $days }} days. If the button doesn’t work, copy this into your browser:
    </p>
    <p style="margin:0; font-family:{{ $font }}; font-size:14px; line-height:1.6; word-break:break-all;">
        <a href="{{ $confirmUrl }}" style="color:{{ $euc }};">{{ $confirmUrl }}</a>
    </p>

</x-mail.layout>
