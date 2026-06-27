{% for period in periods %}
    <div class="card">
        <div class="card-header">
            <h3 class="card-title"><a href="{{ period.route }}">{{ period.title }}</a>
            </h3>
        </div>
        <div class="card-body p-0">
            <table class="table table-hover">
                {% if period.total_transactions > 0 %}
                    <tr>
                        <td class="third">{{ __('firefly.transactions') }}</td>
                        <td class="text-end">{{ period.total_transactions }}</td>
                    </tr>
                @endif
                {% for entry in period.spent %}
                    {% if entry.amount != 0 %}
                        <tr>
                            <td class="third">{{ __('firefly.spent') }}</td>
                            <td class="text-end">
                                <span title="{{ entry.count }}">
                                    {!! format_amount_by_symbol(entry.amount, entry.currency_symbol, entry.currency_decimal_places) }}
                                </span>
                            </td>
                        </tr>
                    @endif
                @endforeach

                {% for entry in period.earned %}
                    {% if entry.amount != 0 %}
                        <tr>
                            <td class="third">{{ __('firefly.earned') }}</td>
                            <td class="text-end">
                                <span title="{{ entry.count }}">
                                    {% if entry.amount < 0 %}
                                        {!! format_amount_by_symbol(entry.amount*-1, entry.currency_symbol, entry.currency_decimal_places) }}
                                    @else
                                        {!! format_amount_by_symbol(entry.amount, entry.currency_symbol, entry.currency_decimal_places) }}
                                    @endif
                                </span>
                            </td>
                        </tr>
                    @endif
                @endforeach

                {% for entry in period.transferred %}
                    {% if entry.amount != 0 %}
                        <tr>
                            <td class="third">{{ 'transferred'|_ }}</td>
                            <td class="text-end">
                                <span title="{{ entry.count }}">
                                    {!! format_amount_by_symbol(entry.amount*-1, entry.currency_symbol, entry.currency_decimal_places) }}
                                </span>
                            </td>
                        </tr>
                    @endif
                @endforeach

                {% for entry in period.transferred_away %}
                    {% if entry.amount != 0 %}
                        <tr>
                            <td class="third">{{ 'transferred_away'|_ }}</td>
                            <td class="text-end">
                                <span title="{{ entry.count }}">
                                    {% if entry.amount < 0 %}
                                        {!! format_amount_by_symbol(entry.amount, entry.currency_symbol, entry.currency_decimal_places) }}
                                    @else
                                        {!! format_amount_by_symbol(entry.amount*-1, entry.currency_symbol, entry.currency_decimal_places) }}
                                    @endif
                                </span>
                            </td>
                        </tr>
                    @endif
                @endforeach

                {% for entry in period.transferred_in %}
                    {% if entry.amount != 0 %}
                        <tr>
                            <td class="third">{{ 'transferred_in'|_ }}</td>
                            <td class="text-end">
                                <span title="{{ entry.count }}">
                                    {% if entry.amount < 0 %}
                                        {!! format_amount_by_symbol(entry.amount*-1, entry.currency_symbol, entry.currency_decimal_places) }}
                                    @else
                                        {!! format_amount_by_symbol(entry.amount, entry.currency_symbol, entry.currency_decimal_places) }}
                                    @endif
                                </span>
                            </td>
                        </tr>
                    @endif
                @endforeach
            </table>
        </div>
    </div>

@endforeach
