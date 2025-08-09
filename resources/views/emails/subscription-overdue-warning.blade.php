@component('mail::message')
{{ trans('email.subscription_overdue_warning_intro', ['name' => $bill->name]) }}

@foreach($dates['pay_dates'] as $date)
  - {{ $date }}
@endforeach

{{ trans('email.subscription_overdue_please_action', ['name' => $bill->name]) }}

@endcomponent
