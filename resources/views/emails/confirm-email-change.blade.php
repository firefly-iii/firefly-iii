@component('mail::message')
{{ trans('email.email_change_body_to_new') }}

{{trans('email.email_change_old', ['email' =>  $oldEmail]) }}

{{trans('email.email_change_new', ['email' => $newEmail]) }}

{{ trans('email.email_change_instructions') }}

{{ $url }}
@endcomponent
