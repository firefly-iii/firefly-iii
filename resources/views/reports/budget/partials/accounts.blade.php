<table class="table table-hover sortable">
    <thead>
    <tr>
        <th data-defaultsign="az">{{ __('firefly.name') }}</th>
        <th data-defaultsign="_19" class="text-end">{{ __('firefly.spent') }}</th>
    </tr>
    </thead>
    <tbody>
    @foreach($report as $account)
        @foreach($account['currencies'] as $currency)
            <tr>
                <td data-value="{{ $account['name'] }} ({{ $currency['currency_name'] }})">
                    <a href="{{ route('accounts.show', $account['id']) }}" title="{{ $account['iban'] }}">{{ $account['name'] }} ({{ $currency['currency_name'] }})</a>
                </td>
                <td data-value="{{ $currency['sum'] }}" class="text-end">
                    {!! format_amount_by_symbol($currency['sum'], $currency['currency_symbol'], $currency['currency_decimal_places']) !!}
                </td>
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
        </tr>
    @endforeach
    </tfoot>
</table>
