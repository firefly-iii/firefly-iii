@component('mail::message')
{{ trans('email.reset_pw_instructions') }}

{{ trans('email.reset_pw_warning') }}

{{ $url }}

@endcomponent
