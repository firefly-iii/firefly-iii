{{-- this is spent in budget limits: --}}
{% for budgetLimit in budget.budgeted %}
    <span title="{{ 'spent_this_period'|_ }}">{{ format_amount_by_symbol(budgetLimit.spent, budgetLimit.currency_symbol, budgetLimit.currency_decimal_places) }}</span>
    {% if 0 == budgetLimit.active_days_passed %}
        <span title="{{ trans('firefly.spent_this_period_per_day', {days: 0}) }}">({{ format_amount_by_symbol(budgetLimit.spent, budgetLimit.currency_symbol, budgetLimit.currency_decimal_places) }})</span>
    @else
        <span title="{{ trans('firefly.spent_this_period_per_day', {days: budgetLimit.active_days_passed}) }}">({{ format_amount_by_symbol(budgetLimit.spent / budgetLimit.active_days_passed, budgetLimit.currency_symbol, budgetLimit.currency_decimal_places) }})</span>
    @endif
<br />
@endforeach

{{-- this is spent NOT in budget limits: --}}
{% for spent in budget.spent %}
    {% if 0 != bccomp('0', spent.spent_outside)  %}
        <span title="{{ 'spent_in_budget_limit_outside_period'|_ }}">{{ format_amount_by_symbol(spent.spent_outside, spent.currency_symbol, spent.currency_decimal_places) }}</span>
        {% if 0 == activeDaysPassed %}
            <span title="{{ trans('firefly.spent_in_budget_limit_outside_period_per_day', {days: 0}) }}">({{ format_amount_by_symbol(spent.spent_outside, spent.currency_symbol, spent.currency_decimal_places) }})</span>
        @else
            <span title="{{ trans('firefly.spent_in_budget_limit_outside_period_per_day', {days: activeDaysPassed}) }}">({{ format_amount_by_symbol(spent.spent_outside / activeDaysPassed, spent.currency_symbol, spent.currency_decimal_places) }})</span>
            @endif
        <br />
    @endif
@endforeach

{{--


{% for spentInfo in budget.spent %}
    {{ format_amount_by_symbol(spentInfo.spent, spentInfo.currency_symbol, spentInfo.currency_decimal_places) }}
    {% if 0 == activeDaysPassed %}
        ({{ format_amount_by_symbol(spentInfo.spent, spentInfo.currency_symbol, spentInfo.currency_decimal_places) }})
    @else
        ({{ format_amount_by_symbol(spentInfo.spent / activeDaysPassed, spentInfo.currency_symbol, spentInfo.currency_decimal_places) }})
    @endif
    <br/>
@endforeach
{% for budgetLimit in budget.budgeted %}
    {% if null == budget.spent[budgetLimit.currency_id] %}
        {{ format_amount_by_symbol(0, budgetLimit.currency_symbol, budgetLimit.currency_decimal_places) }}<br/>
    @endif
@endforeach
--}}
