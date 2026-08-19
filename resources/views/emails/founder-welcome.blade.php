@php
    $ink = '#1B2940';
    $inkSecondary = '#4F5B70';
    $linen = '#F8F4EE';
    $euc = '#274B44';
    $mustardBg = '#FCEACF';
    $mustardText = '#785300';
    $font = "'Livvic', -apple-system, 'Segoe UI', Roboto, Helvetica, Arial, sans-serif";
    $number = $registration->founder_number;
@endphp

<x-mail.layout
    :title="$number ? 'You’re Frith Founder #'.$number : 'You’re a Frith Founder'"
    preview="You're registered. One tap confirms your email."
    footnote="You’re getting this because you registered at frith.community. If that wasn’t you, ignore this email and nothing happens — or unsubscribe below and we’ll delete what we hold."
    :unsubscribe-url="$unsubscribeUrl"
>

    @if ($number)
        <p style="margin:0 0 20px 0;">
            <span style="display:inline-block; background:{{ $mustardBg }}; color:{{ $mustardText }}; font-family:{{ $font }}; font-size:14px; font-weight:600; letter-spacing:0.03em; padding:8px 16px; border-radius:999px;">
                Frith Founder&nbsp;#{{ $number }}
            </span>
        </p>
    @endif

    <h1 style="margin:0 0 16px 0; font-family:{{ $font }}; font-size:24px; line-height:1.3; font-weight:600; color:{{ $ink }};">
        Thank you, {{ $registration->first_name }}.
    </h1>

    <p style="margin:0 0 20px 0; font-family:{{ $font }}; font-size:16px; line-height:1.6; color:{{ $inkSecondary }};">
        You’re registered, and you’re in from the beginning — which means Frith’s premium
        features stay free for your family for good.
    </p>

    <p style="margin:0 0 24px 0; font-family:{{ $font }}; font-size:16px; line-height:1.6; color:{{ $inkSecondary }};">
        One last thing: tap below so we know this address is really yours. Until you do,
        we can’t email you when Frith opens.
    </p>

    {{-- Table-based button: Outlook ignores padding on an anchor. --}}
    <table role="presentation" cellpadding="0" cellspacing="0" border="0" style="margin:0 0 24px 0;">
        <tr>
            <td align="center" bgcolor="{{ $euc }}" style="border-radius:999px;">
                <a href="{{ $verifyUrl }}"
                   style="display:inline-block; padding:15px 28px; font-family:{{ $font }}; font-size:16px; font-weight:500; line-height:1.2; color:{{ $linen }}; text-decoration:none; border-radius:999px;">
                    Confirm my email
                </a>
            </td>
        </tr>
    </table>

    @unless ($registration->isComplete())
        <p style="margin:0 0 24px 0; padding:14px 16px; background:#F0ECE6; border-radius:10px; font-family:{{ $font }}; font-size:15px; line-height:1.6; color:{{ $inkSecondary }};">
            You stopped part-way through the questions, which is completely fine — you’re
            registered as you are. If you’d like to finish them,
            <a href="{{ $finishUrl }}" style="color:{{ $euc }};">pick up where you left off</a>.
        </p>
    @endunless

    <p style="margin:0 0 8px 0; font-family:{{ $font }}; font-size:14px; line-height:1.6; color:{{ $inkSecondary }};">
        If the button doesn’t work, copy this into your browser:
    </p>
    <p style="margin:0; font-family:{{ $font }}; font-size:14px; line-height:1.6; word-break:break-all;">
        <a href="{{ $verifyUrl }}" style="color:{{ $euc }};">{{ $verifyUrl }}</a>
    </p>

</x-mail.layout>
