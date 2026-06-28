<table class="table table-hover sortable">
    <thead>
    <tr>
        <th class="half" data-defaultsign="az" data-defaultsort="asc">{{ __('firefly.category') }}</th>
        <th class="quarter" data-defaultsign="_19">{{ __('firefly.spent') }}</th>
        <th class="quarter" data-defaultsign="_19">{{ __('firefly.earned') }}</th>
    </tr>
    </thead>
    <tbody>
    @foreach($together as $categoryId => $entry)
        <tr>
            <td data-value="@if(null === $entry['category'])zzzzzzzzzzz@else{{ $entry['category'] }}@endif">
                @if(null === $entry['category'])
                    <a href="{{ route('categories.no-category') }}">{{ __('firefly.noCategory') }}</a>
                @else
                    <a href="{{ route('categories.show', [$categoryId]) }}">{{ $entry['category'] }}</a>
                @endif
            </td>
            <td data-value="{{ $entry['spent']['grand_total'] }}">
                @if(0 === count($entry['spent']['per_currency']))
                    {!! format_amount_by_currency($primaryCurrency,'0',true) !!}
                @else
                    @foreach($entry['spent']['per_currency'] as $expense)
                        {!! format_amount_by_symbol($expense['sum'], $expense['currency']['symbol'], $expense['currency']['dp']) !!}<br/>
                    @endforeach
                @endif
            </td>
            <td data-value="{{ $entry['earned']['grand_total'] }}">
                @if(0 === count($entry['earned']['per_currency']))
                    {!! format_amount_by_currency($primaryCurrency,'0',true) !!}
                @else
                    @foreach($entry['earned']['per_currency'] as $income)
                        {!! format_amount_by_symbol($income['sum'] * -1, $income['currency']['symbol'], $income['currency']['dp']) !!}<br/>
                    @endforeach
                @endif
            </td>
        </tr>
    @endforeach
    </tbody>
</table>
