@component('mail::message')
{{ trans('email.used_backup_code_intro', ['email' => $user->email]) }}

{{ trans('email.used_backup_code_warning') }}

- {{ trans('email.date_time') }}: {{ $time }}
- {{ trans('email.ip_address') }}: {{ $ip }}
- {{ trans('email.host_name') }}: {{ $host }}
- {{ trans('email.user_agent') }}: {{ $userAgent }}

@endcomponent
