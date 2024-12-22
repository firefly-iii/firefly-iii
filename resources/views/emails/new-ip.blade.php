@component('mail::message')
{{ trans('email.new_ip_body') }}

{{ trans('email.new_ip_warning') }}

- {{ trans('email.ip_address') }}: {{ $ip }}
- {{ trans('email.host_name') }}: {{ $host }}
- {{ trans('email.date_time') }}: {{ $time }}
- {{ trans('email.user_agent') }}: {{ $userAgent }}

@endcomponent
