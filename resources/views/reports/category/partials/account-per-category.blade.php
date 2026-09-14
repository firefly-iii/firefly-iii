<table class="table table-hover sortable">
    <thead>
    <tr>
        <th data-defaultsign="az">{{ __('firefly.name') }}</th>
        @foreach($categories as $category)
            <th data-defaultsign="_19" class="text-end">{{ $category['name'] }}</th>
        @endforeach
    </tr>
    </thead>
    <tbody>
    @foreach($report as $account)
        @foreach($account['currencies'] as $currency)
            <tr>
                <td data-value="{{ $account['name'] }} ({{ $currency['currency_name'] }})">
                    <a href="{{ route('accounts.show', $account['id']) }}" title="{{ $account['iban'] }}">{{ $account['name'] }} ({{ $currency['currency_name'] }})</a>
                </td>
                @foreach($categories as $category)
                    <td class="text-end">
                        @if(array_key_exists($category['id'], $currency['categories']))
                            <span title="{{ __('firefly.earned') }}: {!! format_amount_by_symbol($currency['categories'][$category['id']]['earned'], $currency['currency_symbol'], $currency['currency_decimal_places'], false) !!}, {{ __('firefly.spent') }}: {!! format_amount_by_symbol($currency['categories'][$category['id']]['spent'], $currency['currency_symbol'], $currency['currency_decimal_places'], false) !!}"
                            {!! format_amount_by_symbol($currency['categories'][$category['id']]['sum'], $currency['currency_symbol'], $currency['currency_decimal_places'])  !!}
                            @else
                            &mdash;
                        @endif
                    </td>
                @endforeach
            </tr>
        @endforeach
    @endforeach
    </tbody>
</table>
