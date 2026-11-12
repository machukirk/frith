A family has asked to connect.

{{ $name }}{{ $area ? ', '.$area : '' }}
@if ($children){{ $children }}
@endif
You have {{ $commonality }}.

Respond here:
{{ $url }}

No rush, and no pressure to reply. Nothing happens until you answer, and
saying no closes it quietly: {{ $name }} is not told.

--
You are getting this because a family asked to connect with you on Frith. You
decide whether anything happens next, and nothing does until you say so.

{{ config('frith.company.contact_email') }}
{{ config('frith.company.name') }}
