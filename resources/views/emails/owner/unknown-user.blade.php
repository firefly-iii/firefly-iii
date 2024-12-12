@component('mail::message')
    {{ trans('email.unknown_user_body', ['address' => $address]) }}
@endcomponent
