<table class="table table-hover sortable">
    <thead>
    <tr>
        <th data-defaultsign="az">{{ __('firefly.name') }}</th>
        @foreach($budgets as $budget)
            <th data-defaultsign="_19" class="text-end">{{ $budget->name }}</th>
        @endforeach
    </tr>
    </thead>
    <tbody>
    @foreach($report as $account)
        @foreach($account['currencies'] as $currency)
            <tr>
                <td data-value="{{ $account['name'] }} ({{ $currency['currency_name'] }})">
                    <a href="{{ route('accounts.show', [$account['id']]) }}" title="{{ $account['iban'] }}">{{ $account['name'] }} ({{ $currency['currency_name'] }})</a>
                </td>
                @foreach($budgets as $budget)
                    <td class="text-end">
                        @if(array_key_exists($budget->id, $currency['budgets']))
                            {!! format_amount_by_symbol($currency['budgets'][$budget->id], $currency['currency_symbol'], $currency['currency_decimal_places']) !!}
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
