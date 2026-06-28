<table class="table table-hover sortable">
    <thead>
    <tr>
        <th data-defaultsign="az">{{ __('firefly.name') }}</th>
        @foreach($tags as $tag)
            <th data-defaultsign="_19" class="text-end">{{ $tag['tag'] }}</th>
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
                @foreach($tags as $tag)
                    <td class="text-end">
                        @if(array_key_exists($tag['id'], $currency['tags']))
                            <span title="{{ __('firefly.earned') }}: {!! format_amount_by_symbol($currency['tags'][$tag['id']]['earned'], $currency['currency_symbol'], $currency['currency_decimal_places'], false) !!}, {{ __('firefly.spent') }}: {!! format_amount_by_symbol($currency['tags'][$tag['id']]['spent'], $currency['currency_symbol'], $currency['currency_decimal_places'], false) !!}">

                            {!! format_amount_by_symbol($currency['tags'][$tag['id']]['sum'], $currency['currency_symbol'], $currency['currency_decimal_places']) !!}
                        @else
                            &mdash;
                        @endif
                    </td>
                @endforeach
            </tr>
        @endforeach
    @endforeach
    </tbody>
    <tfoot>
    <tr>
        <td colspan="{{ 1 + count($tags) }}">
            <p class="text-info">
                <em>{{ __('firefly.tag_report_expenses_listed_once') }}</em>
            </p>
        </td>
    </tr>
    </tfoot>
</table>
