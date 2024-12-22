@component('mail::message')
{{ trans('email.new_backup_codes_intro', ['email' => $user->email]) }}

{{ trans('email.new_backup_codes_warning') }}

- {{ trans('email.date_time') }}: {{ $time }}
- {{ trans('email.ip_address') }}: {{ $ip }}
- {{ trans('email.host_name') }}: {{ $host }}
- {{ trans('email.user_agent') }}: {{ $userAgent }}

@endcomponent
