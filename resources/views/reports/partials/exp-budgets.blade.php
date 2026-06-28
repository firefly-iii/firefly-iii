<table class="table table-hover sortable">
    <thead>
    <tr>
        <th class="sixty-six" data-defaultsign="az" data-defaultsort="asc">{{ __('firefly.category') }}</th>
        <th class="third" data-defaultsign="_19">{{ __('firefly.spent') }}</th>
    </tr>
    </thead>
    <tbody>
    @foreach($together as $budgetId => $entry)
        <tr>
            <td data-value="@if(null === $entry['budget'])zzzzzzzzzzz@else{{ $entry['budget'] }}@endif">
                @if(null === $entry['budget'])
                    <a href="{{ route('budgets.no-budget') }}">{{ __('firefly.no_budget_squared') }}</a>
                @else
                    <a href="{{ route('budgets.show', [$budgetId]) }}">{{ $entry['budget'] }}</a>
                @endif
            </td>
            <td data-value="{{ $entry['spent']['grand_total'] }}">
                @if(count($entry['spent']['per_currency']) === 0)
                    {{ format_amount_by_currency($primaryCurrency, '0', true) }}
                @else
                    @foreach($entry['spent']['per_currency'] as $expense)
                        {!! format_amount_by_symbol($expense['sum'], $expense['currency']['symbol'], $expense['currency']['dp']) !!}<br/>
                    @endforeach
                @endif
            </td>
        </tr>
    @endforeach
    </tbody>
</table>
