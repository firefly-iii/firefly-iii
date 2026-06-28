<table class="table table-hover sortable">
    <thead>
    <tr>
        <th class="half" data-defaultsign="az">{{ __('firefly.name') }}</th>
        <th class="quarter" class="hidden-xs" data-defaultsign="_19">{{ __('firefly.spent') }}</th>
        <th class="quarter" class="hidden-xs" data-defaultsign="_19">{{ __('firefly.earned') }}</th>
    </tr>
    </thead>
    <tbody>
    {% for name, amounts in result %}
        <tr>
            <td data-value="{{ name }}">{{ name }}</td>
            <td data-value="{{ amounts.spent.grand_sum }}">
                {% if amounts.spent.per_currency|length == 0 %}
                    {{ '0'|formatAmount }}
                @endif
                {% for expense in amounts.spent.per_currency %}
                    {!! format_amount_by_symbol(expense.sum, expense.currency.symbol, expense.currency.dp) }}<br/>
                @endforeachkvr

            </td>
            <td data-value="{{ amounts.earned.grand_sum }}">
                {% if amounts.earned.per_currency|length == 0 %}
                    {{ '0'|formatAmount }}
                @endif
                {% for income in amounts.earned.per_currency %}
                    {!! format_amount_by_symbol(income.sum * -1, income.currency.symbol, income.currency.dp) }}<br/>
                @endforeach
            </td>
        </tr>
    @endforeach
    </tbody>
</table>
