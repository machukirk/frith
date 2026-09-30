{{-- Plain text on purpose. It is an internal message, and the thing that
     matters is that it is quick to read and quick to reply to. --}}
{{ $fields['topic'] }}

From: {{ $fields['name'] }} <{{ $fields['email'] }}>

{{ $fields['message'] }}

--
Sent from the help page at {{ route('help') }}
Reply to this email and it goes straight back to them.
