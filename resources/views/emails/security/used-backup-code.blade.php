@component('mail::message')
{{ trans('email.used_backup_code_intro', ['email' => $user->email]) }}

{{ trans('email.used_backup_code_warning') }}

@endcomponent
