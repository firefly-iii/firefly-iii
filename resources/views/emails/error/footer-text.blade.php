
{{ trans('email.closing') }}

{{ trans('email.signature') }}

@if('' !== $ip)
{{ trans('email.footer_ps', ['ipAddress' => $ip]) }}
@endif
