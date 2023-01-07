@component('mail::message')
{{ trans('email.admin_new_user_registered', ['id' => $id,'email' => $email]) }}
@endcomponent
