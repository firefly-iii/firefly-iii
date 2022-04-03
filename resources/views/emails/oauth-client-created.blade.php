@component('mail::message')
{{ trans('email.oauth_created_body', ['name' => $client->name, 'url' => $client->redirect]) }}

{{ trans('email.oauth_created_explanation') }}

{{ trans('email.oauth_created_undo', ['url' => route('profile.index')] ) }}

@endcomponent
