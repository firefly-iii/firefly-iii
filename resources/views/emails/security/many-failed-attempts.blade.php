@component('mail::message')
{{ trans('email.mfa_many_failed_attempts_intro', ['email' => $user->email, 'count' => $count]) }}

{{ trans('email.mfa_many_failed_attempts_warning') }}

- {{ trans('email.date_time') }}: {{ $time }}
- {{ trans('email.ip_address') }}: {{ $ip }}
- {{ trans('email.host_name') }}: {{ $host }}
- {{ trans('email.user_agent') }}: {{ $userAgent }}

@endcomponent
