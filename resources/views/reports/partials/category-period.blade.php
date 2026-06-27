<table class="table table-hover sortable table-sm">
    <thead>
    <tr>
        <th data-defaultsign="az" colspan="2">{{ __('firefly.category') }}</th>
        {% for period in periods %}
            <th data-defaultsign="_19" class="text-end">{{ period }}</th>
        @endforeach
        <th data-defaultsign="_19" class="text-end">{{ __('firefly.sum') }}</th>
    </tr>
    </thead>
    <tbody>
    {% for info in report %}
        <tr>
            <td data-value="{{ info.title }}">
                {% if info.id != 0 %}
                    <a class="btn btn-outline-secondary btn-xs" href="{{ route('categories.show', [info.id]) }}"><span class="fa fa-external-link"></span></a>
                @else
                    <a class="btn btn-outline-secondary btn-xs" href="{{ route('categories.no-category') }}"><span class="fa fa-external-link"></span></a>
                @endif
            </td>
            <td data-value="{{ info.title }}">
                <a title="{{ info.title }}" href="#" data-currency="{{ info.currency_id }}" data-category="{{ info.id }}" class="category-chart-activate">{{ info.title }}</a>
            </td>
            {% for key, period in periods %}
                {{-- income first --}}
                {% if(info.entries[key]) %}
                    <td data-value="{{ info.entries[key] }}" class="text-end">
                        {!! format_amount_by_symbol(info.entries[key], info.currency_symbol, info.currency_decimal_places) }}
                    </td>
                @else
                    <td data-value="0" class="text-end">
                        {!! format_amount_by_symbol(0, info.currency_symbol, info.currency_decimal_places) }}
                    </td>
                @endif
            @endforeach

            {{-- if sum of income, display: --}}
            {% if info.sum %}
                <td data-value="{{ info.sum }}" class="text-end">
                    {!! format_amount_by_symbol(info.sum, info.currency_symbol, info.currency_decimal_places) }}
                </td>
            @else
                <td data-value="0" class="text-end">
                    {!! format_amount_by_symbol(0, info.currency_symbol, info.currency_decimal_places) }}
                </td>
            @endif
        </tr>
    @endforeach
    </tbody>
</table>
