@php
    $ink = '#1B2940';
    $inkSecondary = '#4F5B70';
    $linen = '#F8F4EE';
    $euc = '#274B44';
    $eucPale = '#D6DFDD';
    $eucTint = '#DFF1EF';
    $eucDeep = '#00322F';
    $muted = '#5D5A56';
    $font = "'Livvic', -apple-system, 'Segoe UI', Roboto, Helvetica, Arial, sans-serif";
@endphp

<x-mail.layout
    title="A family has asked to connect"
    preview="Somebody would like to talk. There is no rush, and nothing happens until you answer."
    footnote="You are getting this because a family asked to connect with you on Frith. You decide whether anything happens next, and nothing does until you say so."
    :unsubscribe-url="route('account.notifications')"
>

    <h1 style="margin:0 0 20px 0; font-family:{{ $font }}; font-size:24px; line-height:1.3; font-weight:600; color:{{ $ink }};">
        A family has asked to connect
    </h1>

    {{-- Everything shown about them comes from FamilyCard, the one place that
         decides what crosses between two families before they have agreed to
         talk. An email that assembled its own version would be a second
         answer to the same question, in a channel nobody is checking, which
         is how a promise like this gets broken quietly. --}}
    <table role="presentation" cellpadding="0" cellspacing="0" border="0" style="margin:0 0 20px 0;">
        <tr>
            <td style="padding:0 14px 0 0; vertical-align:middle;">
                <table role="presentation" cellpadding="0" cellspacing="0" border="0">
                    <tr>
                        <td align="center" bgcolor="{{ $eucPale }}"
                            style="width:48px; height:48px; border-radius:24px; font-family:{{ $font }}; font-size:19px; font-weight:600; color:{{ $euc }};">
                            {{ $card->family->initial() }}
                        </td>
                    </tr>
                </table>
            </td>
            <td style="vertical-align:middle;">
                <p style="margin:0; font-family:{{ $font }}; font-size:16px; font-weight:600; color:{{ $ink }};">
                    {{ $name }}@if ($area), {{ $area }}@endif
                </p>
                @if ($children)
                    <p style="margin:3px 0 0 0; font-family:{{ $font }}; font-size:14px; color:{{ $muted }};">{{ $children }}</p>
                @endif
            </td>
        </tr>
    </table>

    <p style="margin:0 0 24px 0; padding:12px 16px; background-color:{{ $eucTint }}; border-radius:10px; font-family:{{ $font }}; font-size:15px; line-height:1.5; color:{{ $eucDeep }};">
        You have {{ $commonality }}.
    </p>

    {{-- Table-based button: Outlook ignores padding on an anchor. --}}
    <table role="presentation" cellpadding="0" cellspacing="0" border="0" style="margin:0 0 24px 0;">
        <tr>
            <td align="center" bgcolor="{{ $euc }}" style="border-radius:999px;">
                <a href="{{ $url }}"
                   style="display:inline-block; padding:15px 28px; font-family:{{ $font }}; font-size:16px; font-weight:500; line-height:1.2; color:{{ $linen }}; text-decoration:none; border-radius:999px;">
                    Respond to {{ $name }}
                </a>
            </td>
        </tr>
    </table>

    <p style="margin:0; font-family:{{ $font }}; font-size:15px; line-height:1.6; color:{{ $inkSecondary }};">
        No rush, and no pressure to reply. Nothing happens until you answer, and
        saying no closes it quietly. {{ $name }} is not told.
    </p>

</x-mail.layout>
