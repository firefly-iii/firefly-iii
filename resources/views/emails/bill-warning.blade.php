@component('mail::message')
@if($field === 'end_date' && $diff !== 0)
{{ trans('email.bill_warning_end_date', ['name' => $bill->name, 'date' => $bill->end_date->isoFormat(trans('config.month_and_day_js')), 'diff' => $diff]) }}
@endif

@if($field === 'extension_date' && $diff !== 0)
{{ trans('email.bill_warning_extension_date', ['name' => $bill->name, 'date' => $bill->extension_date->isoFormat(trans('config.month_and_day_js')), 'diff' => $diff]) }}
@endif

@if($field === 'end_date' && $diff === 0)
{{ trans('email.bill_warning_end_date_zero', ['name' => $bill->name, 'date' => $bill->end_date->isoFormat(trans('config.month_and_day_js')) ]) }}
@endif

@if($field === 'extension_date' && $diff === 0)
{{ trans('email.bill_warning_extension_date_zero', ['name' => $bill->name, 'date' => $bill->extension_date->isoFormat(trans('config.month_and_day_js')) ]) }}
@endif

{{ trans('email.bill_warning_please_action') }}

@endcomponent
