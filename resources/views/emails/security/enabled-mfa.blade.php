@component('mail::message')
{{ trans('email.have_enabled_mfa', ['email' => $user->email]) }}

{{ trans('email.enabled_mfa_warning') }}

@endcomponent
