@component('mail::message')
{{ trans('email.invitation_created_body', ['email' => $email,'invitee' => $invitee]) }}

- {{ trans('email.date_time') }}: {{ $time }}
- {{ trans('email.ip_address') }}: {{ $ip }}
- {{ trans('email.host_name') }}: {{ $host }}
- {{ trans('email.user_agent') }}: {{ $userAgent }}

@endcomponent
