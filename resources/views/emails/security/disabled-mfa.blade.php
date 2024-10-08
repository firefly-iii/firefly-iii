@component('mail::message')
{{ trans('email.have_disabled_mfa', ['email' => $user->email]) }}

{{ trans('email.disabled_mfa_warning') }}

@endcomponent
