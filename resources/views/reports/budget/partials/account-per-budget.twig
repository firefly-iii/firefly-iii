<table class="table table-hover sortable">
    <thead>
    <tr>
        <th data-defaultsign="az">{{ 'name'|_ }}</th>
        {% for budget in budgets %}
            <th data-defaultsign="_19" class="text-right">{{ budget.name }}</th>
        @endforeach
    </tr>
    </thead>
    <tbody>
    {% for account in report %}
        {% for currency in account.currencies %}
            <tr>
                <td data-value="{{ account.name }} ({{ currency.currency_name }})">
                    <a href="{{ route('accounts.show', account.id) }}" title="{{ account.iban }}">{{ account.name }} ({{ currency.currency_name }})</a>
                </td>
                {% for budget in budgets %}
                    <td class="text-right">
                        {% if currency.budgets[budget.id] %}
                            {{ format_amount_by_symbol(currency.budgets[budget.id], currency.currency_symbol, currency.currency_decimal_places) }}
                        @else
                            &mdash;
                        @endif
                    </td>
                @endforeach
            </tr>
        @endforeach
    @endforeach
    </tbody>
</table>
