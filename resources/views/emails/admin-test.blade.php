@component('mail::message')
{{ trans('email.admin_test_body', ['email' => $email]) }}

{{ trans('email.admin_test_link', ['link' => $link]) }}

@endcomponent
