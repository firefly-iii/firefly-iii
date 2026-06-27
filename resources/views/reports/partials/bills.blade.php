<table class="table table-hover sortable">
    <thead>
    <tr>
        <th data-defaultsign="az">{{ trans('form.name') }}</th>
        <th data-defaultsign="_19" class="text-end hidden-xs">{{ trans('form.amount_min') }}</th>
        <th data-defaultsign="_19" class="text-end hidden-xs">{{ trans('form.amount_max') }}</th>
        <th data-defaultsign="_19">{{ trans('form.expected_on') }}</th>
        <th data-defaultsign="_19" class="text-end">{{ trans('form.paid') }}</th>
    </tr>
    </thead>
    <tbody>
    @foreach($report['bills'] as $bill)
        @if((count($bill['expected_dates']) > 0 || count($bill['paid_moments'])) > 0 && $bill['active'])
            <tr>
                <td>
                    <a href="{{ route('subscriptions.show', [$bill['id']]) }}">{{ $bill['name'] }}</a>
                </td>
                <td class="hidden-xs text-end">
                    {!! format_amount_by_symbol($bill['amount_min'], $bill['currency_symbol'], $bill['currency_decimal_places']) !!}
                </td>
                <td class="hidden-xs text-end">
                    {!! format_amount_by_symbol($bill['amount_max'], $bill['currency_symbol'], $bill['currency_decimal_places']) !!}
                </td>
                <td data-value="{{ $bill['expected_dates'][0]?->format('Y-m-d') }}">
                    @foreach($bill['expected_dates'] as $date)
                        {{ $date->isoFormat($monthAndDayFormat) }}<br/>
                    @endforeach
                </td>
                <td class="text-end">
                    @php
                        $hitCount = 0;
                    @endphp
                    @foreach($bill['paid_moments'] as $journals)
                        @foreach($journals as $journal)
                            @php
                                $hitCount++;
                            @endphp
                            <a title="{{ $journal['date']->isoFormat($monthAndDayFormat) }}"
                               href="{{ route('transactions.show', [$journal['transaction_group_id']]) }}">{{ $journal['description'] }}</a>,
                            {!! format_amount_by_symbol($journal['amount'], $journal['currency_symbol'], $journal['currency_decimal_places']) !!}
                            <br/>
                        @endforeach
                    @endforeach
                    @if(0 === $hitCount)
                        <em>{{ __('firefly.notCharged') }}</em>
                    @endif
                </td>
            </tr>
        @endif
    @endforeach
    </tbody>
</table>
