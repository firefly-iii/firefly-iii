@component('mail::message')
{{ trans('email.oauth_created_body', ['name' => $client->name, 'url' => $client->redirect]) }}

{{ trans('email.oauth_created_explanation') }}

{{ trans('email.oauth_created_undo', ['url' => route('profile.index')] ) }}

- {{ trans('email.ip_address') }}: {{ $ip }}
- {{ trans('email.host_name') }}: {{ $host }}
- {{ trans('email.date_time') }}: {{ $time }}
- {{ trans('email.user_agent') }}: {{ $userAgent }}

@endcomponent
