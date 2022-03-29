@component('mail::message')
{{ trans('email.email_change_body_to_old') }}

{{ trans('email.email_change_ignore')}}

{{trans('email.email_change_old', ['email' => $oldEmail]) }}

{{trans('email.email_change_new', ['email' => $newEmail]) }}

{{ trans('email.email_change_undo_link') }} {{ $url }}
@endcomponent
