@foreach($periods as $period)
<div class="card box-default">
    <div class="card-header">
        <h3 class="card-title"><a href="{{ $period['route'] }}">{{ $period['title'] }}</a>
        </h3>
    </div>
    <div class="card-body p-0">
        <table class="table table-hover">
            @if($period['total_transactions'] > 0)
                <tr>
                    <td class="third">{{ __('firefly.transactions') }}</td>
                    <td class="text-right">{{ $period['total_transactions'] }}</td>
                </tr>
            @endif
            @foreach($period['spent'] ?? [] as $entry)
                @if(($entry['amount'] ?? 0) !== 0)
                    <tr>
                        <td class="third">{{ __('firefly.spent') }}</td>
                        <td class="text-right">
                            <span title="Count: {{ $entry['count'] }}">
                                {!! formatAmountBySymbol($entry['amount'], $entry['currency_symbol'], $entry['currency_decimal_places']) !!}
                            </span>
                        </td>
                    </tr>
                @endif
            @endforeach
            @if(array_key_exists('earned', $entry))
                @foreach($period['earned'] as $entry)
                    @if($entry['amount'] !== 0)
                        <tr>
                            <td class="third">{{ __('firefly.earned') }}</td>
                            <td class="text-right">
                                <span title="Count: {{ $entry['count'] }}">
                                    @if($entry['amount'] < 0)
                                        {!! formatAmountBySymbol($entry['amount']*-1, $entry['currency_symbol'], $entry['currency_decimal_places']) !!}
                                    @else
                                        {!! formatAmountBySymbol($entry['amount'], $entry['currency_symbol'], $entry['currency_decimal_places']) !!}
                                    @endif
                                </span>
                            </td>
                        </tr>
                    @endif
                @endforeach
            @endif
                @foreach($period['transferred'] ?? [] as $entry)
                        @if($entry['amount'] !== 0)
            <tr>
                <td class="third">{{ __('firefly.transferred') }}</td>
                <td class="text-right">
                                <span title="Count: {{ $entry['count'] }}">
                                    {!! formatAmountBySymbol($entry['amount']*-1, $entry['currency_symbol'], $entry['currency_decimal_places']) !!}
                                </span>
                </td>
            </tr>
                        @endif
            @endforeach

            @foreach($period['transferred_away'] ?? [] as $entry)
                            @if($entry['amount'] !== 0)
            <tr>
                <td class="third">{{ __('firefly.transferred_away') }}</td>
                <td class="text-right">
                                <span title="Count: {{ $entry['count'] }}">
                                    @if($entry['amount'] < 0)
                                        {!! formatAmountBySymbol($entry['amount'], $entry['currency_symbol'], $entry['currency_decimal_places']) !!}
                                    @else
                                        {!! formatAmountBySymbol($entry['amount']*-1, $entry['currency_symbol'], $entry['currency_decimal_places']) !!}
                                    @endif
                                </span>
                </td>
            </tr>
                        @endif
            @endforeach

                @foreach($period['transferred_in'] ?? [] as $entry)
                                @if($entry['amount'] !== 0)
            <tr>
                <td class="third">{{ __('firefly.transferred_in') }}</td>
                <td class="text-right">
                                <span title="Count: {{ $entry['count'] }}">
                                    @if($entry['amount'] < 0)
                                        {!! formatAmountBySymbol($entry['amount']*-1, $entry['currency_symbol'], $entry['currency_decimal_places']) !!}
                                    @else
                                        {!! formatAmountBySymbol($entry['amount'], $entry['currency_symbol'], $entry['currency_decimal_places']) !!}
                                    @endif
                                </span>
                </td>
            </tr>
                        @endif
            @endforeach
        </table>
    </div>
</div>

@endforeach
