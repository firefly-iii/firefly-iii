@component('mail::message')
{{ trans('email.have_enabled_mfa', ['email' => $user->email]) }}

{{ trans('email.enabled_mfa_warning') }}

- {{ trans('email.date_time') }}: {{ $time }}
- {{ trans('email.ip_address') }}: {{ $ip }}
- {{ trans('email.host_name') }}: {{ $host }}
- {{ trans('email.user_agent') }}: {{ $userAgent }}

@endcomponent
