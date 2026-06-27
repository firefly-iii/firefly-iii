<table class="table table-hover sortable">
    <thead>
    <tr>
        <th data-defaultsign="az">{{ __('firefly.account') }}</th>
        <th data-defaultsign="az">{{ __('firefly.description') }}</th>
        <th data-defaultsign="month">{{ __('firefly.date') }}</th>
        <th class="quarter" class="hidden-xs" data-defaultsign="_19">{{ __('firefly.amount') }}</th>
    </tr>
    </thead>
    <tbody>
    {% for transaction in sorted %}
        <tr>
            <td data-value="{{ transaction.destination_account_name }}">
                <a href="{{ route('accounts.show',transaction.destination_account_id) }}">{{ transaction.destination_account_name }}</a>
            </td>
            <td data-value="{{ transaction.description }}">{{ transaction.description }}</td>
            <td data-value="{{ transaction.date.format('Y-m-d') }}">
                {{ transaction.date.isoFormat($monthAndDayFormat) }}
            </td>
            <!-- TODO i dont think transactionAmount will work. -->
            <td class="text-end" data-value="{{ transaction.amount}}"><span
                        class="mr-2">

                    {!! format_amount_by_symbol(transaction.amount, transaction.currency_symbol, transaction.currency_decimal_places) }}
                    {% if null != transaction.foreign_amount %}
                        ({!! format_amount_by_symbol(transaction.foreign_amount, transaction.foreign_currency_symbol, transaction.foreign_currency_decimal_places) }})
                    @endif
                </span></td>
        </tr>
    @endforeach
    </tbody>
</table>
