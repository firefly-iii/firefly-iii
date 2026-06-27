<table class="table table-hover sortable">
    <thead>
    <tr>
        <th data-defaultsign="az">{{ __('firefly.name') }}</th>
        <th data-defaultsign="_19" class="text-right hidden-xs">{{ __('firefly.balanceStart') }}</th>
        <th data-defaultsign="_19" class="text-right hidden-xs">{{ __('firefly.balanceEnd') }}</th>
        <th data-defaultsign="_19" class="text-end">{{ __('firefly.difference') }}</th>
    </tr>
    </thead>
    <tbody>
    @foreach($accountReport['accounts'] as $account)
        <tr>
            <td data-value="{{ $account['name'] }}">
                <a href="{{ route('accounts.show', $account['id']) }}" title="{{ $account['name'] }}">{{ $account['name'] }}</a>
            </td>
            <td class="text-right hidden-xs" data-value="{{ $account['start_balance'] }}">
                {!! format_amount_by_symbol($account['start_balance'], $account['currency_symbol'], $account['currency_decimal_places']) !!}
            </td>
            <td class="text-right hidden-xs" data-value="{{ $account['end_balance'] }}">
                {!! format_amount_by_symbol($account['end_balance'], $account['currency_symbol'], $account['currency_decimal_places']) !!}
            </td>
            <td class="text-end"
                data-value="{{ ($account['end_balance'] - $account['start_balance']) }}">
                {!! format_amount_by_symbol($account['end_balance'] - $account['start_balance'], $account['currency_symbol'], $account['currency_decimal_places']) !!}
            </td>
        </tr>
    @endforeach
    <tr>
        <td colspan="4"><em>{{ __('firefly.sumOfSums') }}</em></td>
    </tr>
    @foreach($accountReport['sums'] as $sum)
    <tr>
        <td>
            &nbsp;
        </td>
        <td class="text-end">
            {!! format_amount_by_symbol($sum['start'], $sum['currency_symbol'], $sum['currency_decimal_places']) !!}
        </td>
        <td class="text-end">
            {!! format_amount_by_symbol($sum['end'], $sum['currency_symbol'], $sum['currency_decimal_places']) !!}
        </td>
        <td class="text-end">
            {!! format_amount_by_symbol($sum['difference'], $sum['currency_symbol'], $sum['currency_decimal_places']) !!}
        </td>
    </tr>
    @endforeach
    </tbody>
</table>
