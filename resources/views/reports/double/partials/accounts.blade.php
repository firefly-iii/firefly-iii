<table class="table table-hover sortable">
    <thead>
    <tr>
        <th data-defaultsign="az">{{ __('firefly.name') }}</th>
        <th data-defaultsign="_19" class="text-end">{{ __('firefly.spent') }}</th>
        <th data-defaultsign="_19" class="text-end">{{ __('firefly.earned') }}</th>
        <th data-defaultsign="_19" class="text-end">{{ __('firefly.sum') }}</th>
    </tr>
    </thead>
    <tbody>
    @foreach($report as $account)
        <tr>
            <td>
                @if($account['source_name'] === $account['dest_name'])
                    {{ $account['source_name'] }}
                @else
                    {{ $account['source_name'] }} / {{ $account['dest_name'] }}
                @endif
                @if($iban['source_iban'] !== '' && $account['dest_iban'] !== '')
                    @if($iban['source_iban'] === $iban['dest_iban'])
                        ({{ $account['source_iban'] }})
                    @else
                        ({{ $account['source_iban'] }} / ({{ $account['dest_iban'] }}))
                    @endif
                @endif
            </td>
            <td class="text-end">
                {!! format_amount_by_symbol($account['spent'], $account['currency_symbol'], $account['currency_decimal_places']) !!}
            </td>
            <td class="text-end">
                {!! format_amount_by_symbol($account['earned'], $account['currency_symbol'], $account['currency_decimal_places']) !!}
            </td>
            <td class="text-end">
                {!! format_amount_by_symbol($account['sum'], $account['currency_symbol'], $account['currency_decimal_places']) !!}
            </td>
        </tr>
    @endforeach
    </tbody>
    <tfoot>
    @foreach($sums as $sum)
        <tr>
            <td>{{ __('firefly.sum') }} ({{ $sum['currency_name'] }})</td>
            <td class="text-end">
                {!! format_amount_by_symbol($sum['spent'], $sum['currency_symbol'], $sum['currency_decimal_places']) !!}
            </td>
            <td class="text-end">
                {!! format_amount_by_symbol($sum['earned'], $sum['currency_symbol'], $sum['currency_decimal_places']) !!}
            </td>
            <td class="text-end">
                {!! format_amount_by_symbol($sum['sum'], $sum['currency_symbol'], $sum['currency_decimal_places']) !!}
            </td>
        </tr>
    @endforeach
    </tfoot>
</table>
