{{--
    600px table layout with web-safe fallbacks, per brand guidelines §09.
    Livvic will not load in Outlook, so the stack degrades to Segoe UI without
    the layout moving. Styles are inline because Gmail strips <style> blocks,
    and the logo is a PNG because no major client renders SVG in email.
--}}
@props(['title', 'preview', 'footnote', 'unsubscribeUrl'])

@php
    $ink = '#1B2940';
    $inkSecondary = '#4F5B70';
    $linen = '#F8F4EE';
    $linenBorder = '#DCD8D3';
    $euc = '#274B44';
    $font = "'Livvic', -apple-system, 'Segoe UI', Roboto, Helvetica, Arial, sans-serif";
@endphp
<!DOCTYPE html>
<html lang="en-GB">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="x-apple-disable-message-reformatting">
    <meta name="color-scheme" content="light">
    <meta name="supported-color-schemes" content="light">
    <title>{{ $title }}</title>
</head>
<body style="margin:0; padding:0; width:100%; background-color:{{ $linen }};">

    {{-- Preview text: what the inbox shows next to the subject line. --}}
    <div style="display:none; font-size:1px; color:{{ $linen }}; line-height:1px; max-height:0; max-width:0; opacity:0; overflow:hidden;">
        {{ $preview }}&#8203;&#8203;&#8203;&#8203;&#8203;&#8203;&#8203;&#8203;&#8203;&#8203;
    </div>

    <table role="presentation" cellpadding="0" cellspacing="0" border="0" width="100%" style="background-color:{{ $linen }};">
        <tr>
            <td align="center" style="padding:32px 16px;">
                <table role="presentation" cellpadding="0" cellspacing="0" border="0" width="600" style="width:600px; max-width:100%;">

                    <tr>
                        <td style="padding:0 0 24px 0;">
                            {{-- Served at 264px and shown at 132 so it stays sharp
                                 on a retina screen. If a client blocks images the
                                 email still works: the call to action below is a
                                 background-coloured cell, not a picture. --}}
                            <img src="{{ asset('brand/logo/png/frith-logo-horizontal-fullcolour-email.png') }}"
                                 width="132" height="53" alt="Frith"
                                 style="display:block; width:132px; height:53px; border:0;">
                        </td>
                    </tr>

                    <tr>
                        <td style="background-color:#FFFFFF; border:1px solid {{ $linenBorder }}; border-radius:16px; padding:32px;">
                            {{ $slot }}
                        </td>
                    </tr>

                    <tr>
                        <td style="padding:24px 8px 0 8px;">
                            <p style="margin:0 0 8px 0; font-family:{{ $font }}; font-size:14px; line-height:1.6; color:{{ $inkSecondary }};">
                                {{ $footnote }}
                            </p>
                            <p style="margin:0; font-family:{{ $font }}; font-size:14px; line-height:1.6; color:{{ $inkSecondary }};">
                                <a href="{{ $unsubscribeUrl }}" style="color:{{ $euc }};">Unsubscribe</a>
                                &nbsp;·&nbsp;
                                <a href="mailto:{{ config('frith.company.contact_email') }}" style="color:{{ $euc }};">{{ config('frith.company.contact_email') }}</a>
                                &nbsp;·&nbsp;
                                {{ config('frith.company.name') }}@if (config('frith.company.postal_address')), {{ config('frith.company.postal_address') }}@endif
                            </p>
                        </td>
                    </tr>

                </table>
            </td>
        </tr>
    </table>

</body>
</html>
