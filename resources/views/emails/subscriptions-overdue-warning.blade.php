@component('mail::message')
{{ trans('email.subscriptions_overdue_warning_intro', ['count' => $count]) }}

@foreach($info as $row)
- {{ $row['bill']->name }}:
  @foreach($row['pay_dates'] as $date)
  - {{ $date }}
@endforeach
@endforeach

{{ trans('email.subscriptions_overdue_please_action') }}

{{ trans('email.subscriptions_overdue_outro') }}



@endcomponent
