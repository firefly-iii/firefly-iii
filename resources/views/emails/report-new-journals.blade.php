@component('mail::message')
{{ trans_choice('email.new_journals_header', count($transformed)) }}

@foreach($transformed as $group)
- [{{ $group['transactions'][0]['description'] }}]({{ route('transactions.show', [$group['id']]) }})
@if('withdrawal' === $group['transactions'][0]['type'])
{{ $group['transactions'][0]['currency_code']}} {{ round((float)bcmul($group['transactions'][0]['amount'], '-1'), $group['transactions'][0]['currency_decimal_places']) }}
@endif
@if('deposit' === $group['transactions'][0]['type'])
{{ $group['transactions'][0]['currency_code']}} {{ round((float)bcmul($group['transactions'][0]['amount'], '1'), $group['transactions'][0]['currency_decimal_places']) }}
@endif
@if('transfer' === $group['transactions'][0]['type'])
{{ $group['transactions'][0]['currency_code']}} {{ round((float)bcmul($group['transactions'][0]['amount'], '1'), $group['transactions'][0]['currency_decimal_places']) }}
@endif
@endforeach

@endcomponent
