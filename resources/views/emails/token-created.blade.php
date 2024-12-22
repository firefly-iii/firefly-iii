@component('mail::message')
{{ trans('email.access_token_created_body') }}

{{ trans('email.access_token_created_explanation') }}

{{ trans('email.access_token_created_revoke', ['url' => route('profile.index')]) }}

- {{ trans('email.ip_address') }}: {{ $ip }}
- {{ trans('email.host_name') }}: {{ $host }}
- {{ trans('email.date_time') }}: {{ $time }}
- {{ trans('email.user_agent') }}: {{ $userAgent }}

@endcomponent
