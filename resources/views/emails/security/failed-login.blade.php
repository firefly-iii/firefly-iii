@component('mail::message')
{{ trans('email.failed_login_body', ['email' => $user->email]) }}

{{ trans('email.failed_login_warning') }}

@endcomponent
