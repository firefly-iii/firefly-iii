@component('mail::message')
{{ trans('email.reset_pw_instructions') }}

{{ trans('email.reset_pw_warning') }}

{{ $url }}

- {{ trans('email.ip_address') }}: {{ $ip }}
- {{ trans('email.host_name') }}: {{ $host }}
- {{ trans('email.date_time') }}: {{ $time }}
- {{ trans('email.user_agent') }}: {{ $userAgent }}

@endcomponent
