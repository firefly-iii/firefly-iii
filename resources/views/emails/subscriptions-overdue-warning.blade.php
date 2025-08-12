@component('mail::message')
@if(1 === $count)
{{ trans('email.subscriptions_overdue_warning_intro_single') }}
@endif
@if(1 !== $count)
{{ trans('email.subscriptions_overdue_warning_intro_multi', ['count' => $count]) }}
@endif
@foreach($info as $row)
- {{ $row['bill']->name }}:
  @foreach($row['pay_dates'] as $date)
  - {{ $date }}
@endforeach
@endforeach

@if(1 === $count)
{{ trans('email.subscriptions_overdue_please_action_single') }}
@endif
@if(1 !== $count)
{{ trans('email.subscriptions_overdue_please_action_multi', ['count' => $count]) }}
@endif

{{ trans('email.subscriptions_overdue_outro') }}

@endcomponent
