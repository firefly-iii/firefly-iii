{{-- The amount left can only be shown for actual budget limits. --}}
{% for budgetLimit in budget.budgeted %}
    <span class="left_span" data-currency="{{ budgetLimit.currency_id }}" data-limit="{{ budgetLimit.id }}" data-value="{{ budgetLimit.left }}" class="amount_left">
        {{-- the amount left --}}
        <span title="{{ 'left_in_budget_limit_overview'|_ }}">{{ format_amount_by_symbol(budgetLimit.left, budgetLimit.currency_symbol, budgetLimit.currency_decimal_places) }}</span>

        {{-- if the budget limit is in the past, this is not interesting. --}}
        {{-- if there is nothing left, this is not interesting. --}}
        {% if not budgetLimit.in_past and -1 == bccomp('0',budgetLimit.left) %}
            {% if 0 == budgetLimit.active_days_left %}
                <span title="{{ trans('firefly.left_in_budget_limit_per_day', {days: 0}) }}">({{ format_amount_by_symbol(budgetLimit.left, budgetLimit.currency_symbol, budgetLimit.currency_decimal_places) }})
            @else
                <span title="{{ trans('firefly.left_in_budget_limit_per_day', {days: budgetLimit.active_days_left}) }}">({{ format_amount_by_symbol(budgetLimit.left / budgetLimit.active_days_left, budgetLimit.currency_symbol, budgetLimit.currency_decimal_places) }})</span>
            @endif
        @endif

        {{-- if there is nothing left, just format 0.00 --}}
        {% if not budgetLimit.in_past and -1 != bccomp('0',budgetLimit.left) %}
            <span title="{{ 'nothing_left_in_budget'|_ }}">({{ format_amount_by_symbol('0', budgetLimit.currency_symbol, budgetLimit.currency_decimal_places) }})</span>
        @endif
</span><br />
@endforeach


{{--
{% for spentInfo in budget.spent %}
    {% set countLimit = 0 %}
    <!--   loop each budget limit collected for this budget in this period. -->
    {% for budgetLimit in budget.budgeted %}
        <!-- now looping a single budget limit. -->
        {% if spentInfo.currency_id == budgetLimit.currency_id and budgetLimit.in_range %}
            <!-- the code below is used for budget limits INSIDE the current view range. -->
            {% set countLimit = countLimit + 1 %}

            <span class="left_span" data-currency="{{ spentInfo.currency_id }}" data-limit="{{ budgetLimit.id }}"
                  data-value="{{ spentInfo.spent + budgetLimit.amount }}" class="amount_left">
                 <!--the amount left is automatically calculated.  -->
                {{ format_amount_by_symbol(spentInfo.spent + budgetLimit.amount, spentInfo.currency_symbol, spentInfo.currency_decimal_places) }}
                {% if spentInfo.spent + budgetLimit.amount > 0 %}
                    {% if 0 == activeDaysLeft %}
                        ({{ format_amount_by_symbol(spentInfo.spent + budgetLimit.amount, spentInfo.currency_symbol, spentInfo.currency_decimal_places) }})
                    @else
                        ({{ format_amount_by_symbol((spentInfo.spent + budgetLimit.amount) / activeDaysLeft, spentInfo.currency_symbol, spentInfo.currency_decimal_places) }})
                    @endif
                @else
                    ({{ format_amount_by_symbol(0, spentInfo.currency_symbol, spentInfo.currency_decimal_places) }})
                @endif
                                                    </span>
            <br/>
        @endif

        {% if spentInfo.currency_id == budgetLimit.currency_id and not budgetLimit.in_range and 0.0 == budgetLimit.total_days %}
            <span class="left_span" data-currency="{{ spentInfo.currency_id }}" data-limit="{{ budgetLimit.id }}"
                  data-value="{{ spentInfo.spent + budgetLimit.amount }}" class="amount_left">
                                            {{ format_amount_by_symbol(spentInfo.spent + budgetLimit.amount, spentInfo.currency_symbol, spentInfo.currency_decimal_places) }}
                                        </span>
            <span class="text-muted">({{ 'unknown'|_ }})</span>
        @endif
        {% if spentInfo.currency_id == budgetLimit.currency_id and not budgetLimit.in_range and 0.0 != budgetLimit.total_days %}

            <span class="left_span" data-currency="{{ spentInfo.currency_id }}" data-limit="{{ budgetLimit.id }}"
                  data-value="{{ spentInfo.spent + budgetLimit.amount }}" class="amount_left">
                                            {{ format_amount_by_symbol(spentInfo.spent + budgetLimit.amount, spentInfo.currency_symbol, spentInfo.currency_decimal_places) }}
                                        </span>
            ({{ format_amount_by_symbol((spentInfo.spent + budgetLimit.amount) / budgetLimit.total_days, spentInfo.currency_symbol, spentInfo.currency_decimal_places) }})
        @endif

    @endforeach

    {% if countLimit == 0 %}
        <!--  display nothing -->
    @endif
    -->
@endforeach
                                        {% for budgetLimit in budget.budgeted %}
                                            {% if null == budget.spent[budgetLimit.currency_id] %}
                                                <span class="left_span" data-currency="{{ spentInfo.currency_id }}" data-limit="{{ budgetLimit.id }}"
                                                      data-value="{{ spentInfo.spent + budgetLimit.amount }}" class="amount_left">
                                                {{ format_amount_by_symbol(budgetLimit.amount, budgetLimit.currency_symbol, budgetLimit.currency_decimal_places) }}
                                                    {% if budgetLimit.in_range %}
                                                        {% if 0 == activeDaysLeft %}
                                                            ({{ format_amount_by_symbol(budgetLimit.amount, budgetLimit.currency_symbol, budgetLimit.currency_decimal_places) }})
                                                        @else
                                                            ({{ format_amount_by_symbol(budgetLimit.amount / activeDaysLeft, budgetLimit.currency_symbol, budgetLimit.currency_decimal_places) }})
                                                        @endif
                                                    @endif
                                                    {% if not budgetLimit.in_range %}
                                                        <!-- For issue #10441, add per day if the budget limit is out of range. -->
                                                        ({{ format_amount_by_symbol(budgetLimit.amount / budgetLimit.total_days, budgetLimit.currency_symbol, budgetLimit.currency_decimal_places) }})
                                                    @endif
                                            </span>
                                                <br/>
                                            @endif

@endforeach
--}}
