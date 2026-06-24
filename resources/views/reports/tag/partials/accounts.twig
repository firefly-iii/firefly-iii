<table class="table table-hover sortable">
    <thead>
    <tr>
        <th data-defaultsign="az">{{ 'name'|_ }}</th>
        <th data-defaultsign="_19" class="text-right">{{ __('firefly.spent') }}</th>
        <th data-defaultsign="_19" class="text-right">{{ 'earned'|_ }}</th>
        <th data-defaultsign="_19" class="text-right">{{ 'sum'|_ }}</th>
    </tr>
    </thead>
    <tbody>
    {% for account in report %}
        {% for currency in account.currencies %}
            <tr>
                <td data-value="{{ account.name }} ({{ currency.currency_name }})">
                    <a href="{{ route('accounts.show', account.id) }}" title="{{ account.iban }}">{{ account.name }} ({{ currency.currency_name }})</a>
                </td>
                <td data-value="{{ currency.spent }}" class="text-right">
                    {{ format_amount_by_symbol(currency.spent, currency.currency_symbol, currency.currency_decimal_places) }}
                </td>
                <td data-value="{{ currency.earned }}" class="text-right">
                    {{ format_amount_by_symbol(currency.earned, currency.currency_symbol, currency.currency_decimal_places) }}
                </td>
                <td data-value="{{ currency.sum }}" class="text-right">
                    {{ format_amount_by_symbol(currency.sum, currency.currency_symbol, currency.currency_decimal_places) }}
                </td>
            </tr>
        @endforeach
    @endforeach
    </tbody>
    <tfoot>
    {% for sum in sums %}
        <tr>
            <td>{{ 'sum'|_ }} ({{ sum.currency_name }})</td>
            <td class="text-right">
                {{ format_amount_by_symbol(sum.spent_sum, sum.currency_symbol, sum.currency_decimal_places) }}
            </td>
            <td class="text-right">
                {{ format_amount_by_symbol(sum.earned_sum, sum.currency_symbol, sum.currency_decimal_places) }}
            </td>
            <td class="text-right">
                {{ format_amount_by_symbol(sum.total_sum, sum.currency_symbol, sum.currency_decimal_places) }}
            </td>
        </tr>
    @endforeach
    </tfoot>
</table>
