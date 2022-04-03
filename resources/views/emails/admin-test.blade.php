@component('mail::message')
{{ trans('email.admin_test_body', ['email' => $email]) }}
@endcomponent
