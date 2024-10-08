@component('mail::message')
{{ trans('email.no_backup_codes_intro', ['email' => $user->email]) }}

{{ trans('email.no_backup_codes_warning') }}

@endcomponent
