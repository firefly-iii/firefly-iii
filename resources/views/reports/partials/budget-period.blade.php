<table class="table table-hover sortable">
    <thead>
    <tr>
        <th data-defaultsign="az" colspan="2">{{ __('firefly.budget') }}</th>
        @foreach($periods as $period)
            <th data-defaultsign="_19" class="text-end">{{ $period }}</th>
        @endforeach
        <th data-defaultsign="_19" class="text-end">{{ __('firefly.average') }}</th>
        <th data-defaultsign="_19" class="text-end">{{ __('firefly.sum') }}</th>
    </tr>
    </thead>
    <tbody>
    @foreach($report as $info)
        <tr>
            <td data-value="{{ $info['name'] }}">
                @if($info['id'] !== 0)
                    <a class="btn btn-outline-secondary btn-xs" href="{{ route('budgets.show', [$info['id']]) }}"><span class="fa fa-external-link"></span></a>
                @else
                    <a class="btn btn-outline-secondary btn-xs" href="{{ route('budgets.no-budget') }}"><span class="fa fa-external-link"></span></a>
                @endif
            </td>
            <td data-value="{{ $info['name'] }}">
                <a title="{{ $info['name'] }}" href="#" data-budget="{{ $info['id'] }}" data-currency="{{ $info['currency_id'] }}" class="budget-chart-activate">{{ $info['name'] }}</a>
            </td>
            @foreach($periods as $key => $period)
                @if(array_key_exists($key, $info['entries']))
                    <td data-value="{{ $info['entries'][$key] }}" class="text-end">
                        {!! format_amount_by_symbol($info['entries'][$key], $info['currency_symbol'], $info['currency_decimal_places']) !!}
                    </td>
                @else
                    <td data-value="0" class="text-end">
                        {!! format_amount_by_symbol(0, $info['currency_symbol'], $info['currency_decimal_places']) !!}
                    </td>
                @endif

            @endforeach
            <td data-value="{{ $info['avg'] }}" class="text-end">
                {!! format_amount_by_symbol($info['avg'], $info['currency_symbol'], $info['currency_decimal_places']) !!}
            </td>
            <td data-value="{{ $info['sum'] }}" class="text-end">
                {!! format_amount_by_symbol($info['sum'], $info['currency_symbol'], $info['currency_decimal_places']) !!}
            </td>
        </tr>
    @endforeach
    </tbody>
</table>
