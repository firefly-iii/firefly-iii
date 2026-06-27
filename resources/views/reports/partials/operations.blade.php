<table class="table table-hover sortable">
    <thead>
    <tr>
        <th data-defaultsign="az">{{ __('firefly.currency') }}</th>
        <th data-defaultsign="_19">{{ __('firefly.money_flowing_in')}}</th>
        <th data-defaultsign="_19">{{ __('firefly.money_flowing_out') }}</th>
        <th data-defaultsign="_19">{{ __('firefly.difference') }}</th>
    </tr>
    </thead>
    <tbody>
    @foreach($sums as $sum)
    <tr>
        <td>{{ $sum['currency_name'] }} ({{ $sum['currency_symbol'] }})</td>
        <td data-value="{{ $sum['in'] }}">
            {!! format_amount_by_symbol($sum['in'], $sum['currency_symbol'], $sum['currency_decimal_places']) !!}
        </td>
        <td data-value="{{ $sum['out'] }}">
            {!! format_amount_by_symbol($sum['out'], $sum['currency_symbol'], $sum['currency_decimal_places']) !!}
        </td>
        <td data-value="{{ $sum['sum'] }}">
            {!! format_amount_by_symbol($sum['sum'], $sum['currency_symbol'], $sum['currency_decimal_places']) !!}
        </td>
    </tr>
    @endforeach
    </tbody>
</table>
