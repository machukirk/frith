Hello{{ $registration->first_name ? ', '.$registration->first_name : '' }}.

Tap the link below to log in to Frith. No password needed.

{{ $url }}

It works once, and for the next {{ $minutes }} minutes. If it has expired, ask
for another from the log in page.

--
You are getting this because somebody asked for a link to log in to this
address. If that was not you, ignore this email — nobody can get in without
the link, and it expires on its own.

{{ config('frith.company.contact_email') }}
{{ config('frith.company.name') }}
