<table class="table table-hover sortable">
    <thead>
    <tr>
        <th data-defaultsign="az">{{ __('firefly.name') }}</th>
        <th data-defaultsign="_19" class="text-right hidden-xs">{{ 'balanceStart'|_ }}</th>
        <th data-defaultsign="_19" class="text-right hidden-xs">{{ 'balanceEnd'|_ }}</th>
        <th data-defaultsign="_19" class="text-end">{{ 'difference'|_ }}</th>
    </tr>
    </thead>
    <tbody>
    {% for account in accountReport.accounts %}
        <tr>
            <td data-value="{{ account.name }}">
                <a href="{{ route('accounts.show',account.id) }}" title="{{ account.name }}">{{ account.name }}</a>
            </td>
            <td class="text-right hidden-xs" data-value="{{ account.start_balance }}">
                {{ format_amount_by_symbol(account.start_balance, account.currency_symbol, account.currency_decimal_places) }}
            </td>
            <td class="text-right hidden-xs" data-value="{{ account.end_balance }}">
                {{ format_amount_by_symbol(account.end_balance, account.currency_symbol, account.currency_decimal_places) }}
            </td>
            <td class="text-end"
                data-value="{{ (account.end_balance - account.start_balance) }}">
                {{ format_amount_by_symbol(account.end_balance - account.start_balance, account.currency_symbol, account.currency_decimal_places) }}
            </td>
        </tr>
    @endforeach
    <tr>
        <td colspan="4"><em>{{ 'sumOfSums'|_ }}</em></td>
    </tr>
    {% for sum in accountReport.sums %}
    <tr>
        <td>
            &nbsp;
        </td>
        <td class="text-end">
            {{ format_amount_by_symbol(sum.start, sum.currency_symbol, sum.currency_decimal_places) }}
        </td>
        <td class="text-end">
            {{ format_amount_by_symbol(sum.end, sum.currency_symbol, sum.currency_decimal_places) }}
        </td>
        <td class="text-end">
            {{ format_amount_by_symbol(sum.difference, sum.currency_symbol, sum.currency_decimal_places) }}
        </td>
    </tr>
    @endforeach
    </tbody>
</table>
