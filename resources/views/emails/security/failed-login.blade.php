@component('mail::message')
{{ trans('email.failed_login_body', ['email' => $user->email]) }}

{{ trans('email.failed_login_warning') }}

- {{ trans('email.date_time') }}: {{ $time }}
- {{ trans('email.ip_address') }}: {{ $ip }}
- {{ trans('email.host_name') }}: {{ $host }}
- {{ trans('email.user_agent') }}: {{ $userAgent }}

@endcomponent
