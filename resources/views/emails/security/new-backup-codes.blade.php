@component('mail::message')
{{ trans('email.new_backup_codes_intro', ['email' => $user->email]) }}

{{ trans('email.new_backup_codes_warning') }}

@endcomponent
