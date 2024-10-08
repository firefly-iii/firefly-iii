@component('mail::message')
{{ trans('email.few_backup_codes_intro', ['email' => $user->email, 'count' => $count]) }}

{{ trans('email.few_backup_codes_warning') }}

@endcomponent
