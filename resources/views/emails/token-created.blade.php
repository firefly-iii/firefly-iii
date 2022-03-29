@component('mail::message')
{{ trans('email.access_token_created_body') }}

{{ trans('email.access_token_created_explanation') }}

{{ trans('email.access_token_created_revoke', ['url' => route('profile.index')]) }}
@endcomponent
