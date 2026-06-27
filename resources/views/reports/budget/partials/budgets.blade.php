<table class="table table-hover sortable">
    <thead>
    <tr>
        <th data-defaultsign="az">{{ __('firefly.name') }}</th>
        <th data-defaultsign="_19" class="text-end">{{ __('firefly.spent') }}</th>
        <th data-defaultsign="_19">{{ trans('list.percentage') }}</th>
    </tr>
    </thead>
    <tbody>
    @foreach($report as $budget)
    @if(count($budget['currencies']) === 0)
            <tr>
                <td data-value="{{ $budget['name'] }}">
                    <a href="{{ route('budgets.show', $budget['id']) }}" title="{{ $budget['name'] }}">{{ $budget['name'] }}</a>
                </td>
                <td class="text-end">&mdash;</td>
                <td>&nbsp;</td>
            </tr>
        @endif
    @foreach($budget['currencies'] as $currency)
            <tr>
                <td data-value="{{ $budget['name'] }} ({{ $currency['currency_name'] }})">
                    <a href="{{ route('budgets.show', $budget['id']) }}" title="{{ $budget['name'] }}">{{ $budget['name'] }} ({{ $currency['currency_name'] }})</a>
                </td>
                <td data-value="{{ $currency['sum'] }}" class="text-end">
                    {!! format_amount_by_symbol($currency['sum'], $currency['currency_symbol'], $currency['currency_decimal_places']) !!}
                </td>
                <td data-value="{{ $currency['sum_pct'] }}">{{ $currency['sum_pct'] }}%</td>
            </tr>
        @endforeach
    @endforeach
    </tbody>
    <tfoot>
    @foreach($sums as $sum)
        <tr>
            <td>{{ __('firefly.sum') }} ({{ $sum['currency_name'] }})</td>
            <td class="text-end">
                {!! format_amount_by_symbol($sum['sum'], $sum['currency_symbol'], $sum['currency_decimal_places']) !!}
            </td>
            <td>&nbsp;</td>
        </tr>
    @endforeach
    </tfoot>
</table>
