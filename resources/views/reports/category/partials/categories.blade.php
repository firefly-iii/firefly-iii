<table class="table table-hover sortable">
    <thead>
    <tr>
        <th data-defaultsign="az">{{ __('firefly.name') }}</th>
        <th data-defaultsign="_19" class="text-end">{{ __('firefly.spent') }}</th>
        <th data-defaultsign="_19" class="text-end">{{ 'earned'|_ }}</th>
        <th data-defaultsign="_19" class="text-end">{{ __('firefly.sum') }}</th>
    </tr>
    </thead>
    <tbody>
    {% for category in report %}
        {% if category.currencies|length == 0 %}
            <tr>
                <td data-value="{{ category.name }}">
                    <a href="{{ route('categories.show', category.id) }}" title="{{ category.name }}">{{ category.name }}</a>
                </td>
                <td class="text-end">&mdash;</td>
                <td class="text-end">&mdash;</td>
                <td class="text-end">&mdash;</td>
            </tr>
        @endif
        {% for currency in category.currencies %}
            <tr>
                <td data-value="{{ category.name }} ({{ currency.currency_name }})">
                    <a href="{{ route('categories.show', category.id) }}" title="{{ category.name }}">{{ category.name }} ({{ currency.currency_name }})</a>
                </td>
                <td data-value="{{ currency.spent }}" class="text-end">
                    {{ format_amount_by_symbol(currency.spent, currency.currency_symbol, currency.currency_decimal_places) }}
                </td>
                <td data-value="{{ currency.earned }}" class="text-end">
                    {{ format_amount_by_symbol(currency.earned, currency.currency_symbol, currency.currency_decimal_places) }}
                </td>
                <td data-value="{{ currency.sum }}" class="text-end">
                    {{ format_amount_by_symbol(currency.sum, currency.currency_symbol, currency.currency_decimal_places) }}
                </td>
            </tr>
        @endforeach
    @endforeach
    </tbody>
    <tfoot>
    {% for sum in sums %}
        <tr>
            <td>{{ __('firefly.sum') }} ({{ sum.currency_name }})</td>
            <td class="text-end">
                {{ format_amount_by_symbol(sum.spent_sum, sum.currency_symbol, sum.currency_decimal_places) }}
            </td>
            <td class="text-end">
                {{ format_amount_by_symbol(sum.earned_sum, sum.currency_symbol, sum.currency_decimal_places) }}
            </td>
            <td class="text-end">
                {{ format_amount_by_symbol(sum.total_sum, sum.currency_symbol, sum.currency_decimal_places) }}
            </td>
        </tr>
    @endforeach
    </tfoot>
</table>
