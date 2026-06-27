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
    {% for tag in report %}
        {% if tag.currencies|length == 0 %}
            <tr>
                <td data-value="{{ tag.name }}">
                    <a href="{{ route('tags.show', [tag.id]) }}" title="{{ tag.name }}">{{ tag.name }}</a>
                </td>
                <td class="text-end">&mdash;</td>
                <td class="text-end">&mdash;</td>
                <td class="text-end">&mdash;</td>
            </tr>
        @endif
        {% for currency in tag.currencies %}
            <tr>
                <td data-value="{{ tag.name }} ({{ currency.currency_name }})">
                    <a href="{{ route('tags.show', [tag.id]) }}" title="{{ tag.name }}">{{ tag.name }} ({{ currency.currency_name }})</a>
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
    <tr>
        <td colspan="4">
            <p class="text-info">
                <em>{{ 'tag_report_expenses_listed_once'|_ }}</em>
            </p>
        </td>
    </tr>
    </tfoot>
</table>

