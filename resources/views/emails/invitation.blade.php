@component('mail::message')
{{ trans('email.invitation_introduction', ['host' => $host]) }}

{{ trans('email.invitation_invited_by', ['invitee' => $invitee, 'admin' => $admin]) }}

{{ trans('email.invitation_url', ['url' => $url]) }}

@endcomponent
