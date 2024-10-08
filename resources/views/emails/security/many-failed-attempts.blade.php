@component('mail::message')
{{ trans('email.mfa_many_failed_attempts_intro', ['email' => $user->email, 'count' => $count]) }}

{{ trans('email.mfa_many_failed_attempts_warning') }}

@endcomponent
