{{--
    The plain-text half of the message. Worth the duplication: a message with
    no text part scores worse with spam filters, and it is what gets read by
    a text-only client or a screen reader set to prefer plain text.
--}}
@if ($registration->founder_number)FRITH FOUNDER #{{ $registration->founder_number }}

@endif
Thank you, {{ $registration->first_name }}.

You're registered, and you're in from the beginning — which means Frith's premium features stay free for your family for good.

One last thing: open the link below so we know this address is really yours. Until you do, we can't email you when Frith opens.

{{ $verifyUrl }}
@unless ($registration->isComplete())

You stopped part-way through the questions, which is completely fine — you're registered as you are. If you'd like to finish them, pick up where you left off:

{{ $finishUrl }}
@endunless

--
You're getting this because you registered at frith.community. If that wasn't you, ignore this email and nothing happens — or unsubscribe below and we'll delete what we hold.

Unsubscribe: {{ $unsubscribeUrl }}
{{ config('frith.company.contact_email') }}
{{ config('frith.company.name') }}@if (config('frith.company.postal_address')), {{ config('frith.company.postal_address') }}@endif
