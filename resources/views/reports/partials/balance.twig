<table class="table table-hover">
    <thead>
    <tr>
        <th>{{ 'budgets'|_ }}</th>
        {% for account in report.accounts %}
            {% if account.sum != 0 %}
                <th class="text-right hidden-xs"><a href="{{ route('accounts.show',account.id) }}"
                                                                    title="{{ account.iban|default(account.name) }}">{{ account.name }}</a></th>
            @endif
        @endforeach
        <th class="text-right">{{ 'sum'|_ }}</th>
    </tr>
    </thead>
    <tbody>

    {% for budget in report.budgets %}
        {% if budget.spent|length > 0 %}
            <tr>
                <td>
                    <a href="{{ route('budgets.show', [budget.budget_id]) }}">{{ budget.budget_name }}</a>
                </td>
                {% for account in report.accounts %}
                    {% if budget.spent[$account->id] %}
                        <td class="text-right">
                            {{ format_amount_by_symbol(budget.spent[$account->id].spent, budget.spent[$account->id].currency_symbol, budget.spent[$account->id].currency_decimal_places) }}
                            <span data-location="budget-entry"
                               data-budget-id="{{ budget.budget_id }}"
                               data-account-id="{{ account.id }}"
                               data-currency-id="{{ budget.spent[$account->id].currency_id }}"
                               class="fa fa-info-circle text-muted firefly-info-button"></span>
                        </td>
                    @else
                        {% if report.accounts[$account->id].sum != 0 %}
                            <td>&nbsp;</td>
                        @endif
                    @endif

                @endforeach
                <td class="text-right">
                    {% for sum in report.sums[budget.budget_id] %}
                        {{ format_amount_by_symbol(sum.sum, sum.currency_symbol, sum.currency_decimal_places) }}
                        <br/>
                    @endforeach
                </td>
            </tr>
        @endif
    @endforeach
    </tbody>
    <tfoot>
    <tr>
        <td><em>{{ 'sum'|_ }}</em></td>
        {% for account in report.accounts %}
            {% if account.sum != 0 %}
                <td class="text-right">
                    {{ format_amount_by_symbol(account.sum, account.currency_symbol, account.currency_decimal_places) }}
                </td>
            @endif
        @endforeach
    </tr>
    </tfoot>
</table>
