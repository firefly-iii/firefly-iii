@component('mail::message')
{{ trans('email.invitation_created_body', ['email' => $email,'invitee' => $invitee]) }}
@endcomponent
