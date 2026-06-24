<table class="table table-hover table-compressed table-responsive ">
    <thead>
    <tr class="ignore">
        <th class="hide-buttons">&nbsp;</th>
        <th class="hide-icon">&nbsp;</th>

        <th class="hide-description">{{ trans('list.description') }}</th>
        <th class="text-right hide-balance_before">{{ trans('list.balance_before') }}</th>
        <th class="text-right hide-amount">{{ trans('list.amount') }}</th>
        <th class="text-right hide-balance_after">{{ trans('list.balance_after') }}</th>

        <th class="hide-date">{{ trans('list.date') }}</th>
        {{-- new optional fields (3x) --}}
        <th class="hide-from">{{ trans('list.from') }}</th>
        <th class="hide-to">{{ trans('list.to') }}</th>

        <th class="hide-budget"><span class="bi bi-pie-chart" title="{{ trans('list.budget') }}"></span></th>
        <th class="hide-category"><span class="bi bi-bookmark" title="{{ trans('list.category') }}"></span></th>
        <th class="hide-bill">{{ trans('list.bill') }}</th>

        {{-- more optional fields (2x) --}}
        <th class="hide-create_date">{{ trans('list.create_date') }}</th>
        <th class="hide-update_date">{{ trans('list.update_date') }}</th>

        <th class="hide-notes">{{ trans('list.notes') }}</th>

        {{-- even more optional fields --}}
        <th class="hide-interest_date">{{ trans('list.interest_date') }}</th>
        <th class="hide-book_date">{{ trans('list.book_date') }}</th>
        <th class="hide-process_date">{{ trans('list.process_date') }}</th>
        <th class="hide-due_date">{{ trans('list.due_date') }}</th>
        <th class="hide-payment_date">{{ trans('list.payment_date') }}</th>
        <th class="hide-invoice_date">{{ trans('list.invoice_date') }}</th>

    </tr>
    </thead>
    <tbody>
    {% for journal in journals %}
        <tr data-date="{{ journal.date.format('Y-m-d') }}" data-id="{{ journal.id }}">

            <td class="hide-buttons">
                <div class="btn-group btn-group-sm">
                    <a href="{{ route('transactions.edit',journal.transaction_group_id) }}" class="btn btn-xs btn-outline-secondary"><span class="bi bi-pencil"></span></a>
                    <a href="{{ route('transactions.delete',journal.transaction_group_id) }}" class="btn btn-xs btn-danger"><span class="bi bi-trash"></span></a>
                </div>
            </td>

            <td class="hide-icon">
                {% if journal.transaction_type_type == 'Withdrawal' %}
                    <span class="bi bi-arrow-left" title="{{ trans('firefly.Withdrawal') }}"></span>
                @endif

                {% if journal.transaction_type_type == 'Deposit' %}
                    <span class="bi bi-arrow-right" title="{{ trans('firefly.Deposit') }}"></span>
                @endif

                {% if journal.transaction_type_type == 'Transfer' %}
                    <span class="bi bi-arrow-left-right" title="{{ trans('firefly.Deposit') }}"></span>
                @endif

                {% if journal.transaction_type_type == 'Reconciliation' %}
                    <span class="bi bi-calculator" title="{{ trans('firefly.reconciliation_transaction') }}"></span>
                @endif

                {% if journal.transaction_type_type == 'Opening balance' %}
                    <span class="bi bi-star" title="{{ trans('firefly.Opening balance') }}"></span>
                @endif

            </td>

            <td class="hide-description">
                <a href="{{ route('transactions.show',journal.transaction_group_id) }}">
                    {% if journal.group_title|length > 0 %}
                        {{ journal.group_title }} ({{ journal.description }})
                    @else
                        {{ journal.description }}
                    @endif
                </a>
            </td>

            <td class="text-right hide-balance_before">
                {{ format_amount_by_symbol(journal.balance_before, auditData[$account->id].currency.symbol, auditData[$account->id].currency.decimal_places) }}
            </td>
            <td class="text-right hide-amount">

                {% if auditData[$account->id].currency.id == journal.currency_id %}
                    {% if account.id == journal.destination_account_id and journal.transaction_type_type == 'Opening balance' %}
                        {{ format_amount_by_symbol(journal.amount*-1, journal.currency_symbol, journal.currency_decimal_places) }}
                    {% elseif account.id == journal.destination_account_id and journal.transaction_type_type == 'Deposit' %}
                        {{ format_amount_by_symbol(journal.amount*-1, journal.currency_symbol, journal.currency_decimal_places) }}
                    {% elseif account.id == journal.destination_account_id and journal.transaction_type_type == 'Transfer' %}
                        {{ format_amount_by_symbol(journal.amount*-1, journal.currency_symbol, journal.currency_decimal_places) }}
                    @else
                        {{ format_amount_by_symbol(journal.amount, journal.currency_symbol, journal.currency_decimal_places) }}
                    @endif
                @endif

                {% if auditData[$account->id].currency.id == journal.foreign_currency_id %}
                    {% if account.id == journal.destination_account_id and journal.transaction_type_type == 'Opening balance' %}
                        {{ format_amount_by_symbol(journal.foreign_amount*-1, journal.foreign_currency_symbol, journal.foreign_currency_decimal_places) }}
                    {% elseif account.id == journal.destination_account_id and journal.transaction_type_type == 'Deposit' %}
                        {{ format_amount_by_symbol(journal.foreign_amount*-1, journal.foreign_currency_symbol, journal.foreign_currency_decimal_places) }}
                    {% elseif account.id == journal.destination_account_id and journal.transaction_type_type == 'Transfer' %}
                        {{ format_amount_by_symbol(journal.foreign_amount*-1, journal.foreign_currency_symbol, journal.foreign_currency_decimal_places) }}
                    @else
                        {{ format_amount_by_symbol(journal.foreign_amount, journal.foreign_currency_symbol, journal.foreign_currency_decimal_places) }}
                    @endif
                @endif

            </td>

            <td class="text-right hide-balance_after">
                {{ format_amount_by_symbol(journal.balance_after, auditData[$account->id].currency.symbol, auditData[$account->id].currency.decimal_places) }}
            </td>

            <td class="hide-date">{{ journal.date.isoFormat($monthAndDayFormat) }}</td>

            <td class="hide-from">
                <a href="{{ route('accounts.show', [journal.source_account_id]) }}" title="{{ journal.source_account_iban|default(journal.source_account_name) }}">{{ journal.source_account_name }}</a>
            </td>

            <td class="hide-to">
                <a href="{{ route('accounts.show', [journal.destination_account_id]) }}" title="{{ journal.destination_account_iban|default(journal.destination_account_name) }}">{{ journal.destination_account_name }}</a>
            </td>
            <td class="hide-budget">
                {% if journal.budget_id %}
                    <a href="{{ route('budgets.show', [journal.budget_id]) }}" title="{{ journal.budget_name }}">{{ journal.budget_name }}</a>
                @endif
            </td>
            <td class="hide-category">
                {% if journal.category_id %}
                    <a href="{{ route('categories.show', [$journal['category_id']]) }}" title="{{ journal['category_name'] }}">{{ journal['category_name'] }}</a>
                @endif
            </td>
            <td class="hide-bill">
                {% if journal.bill_id %}
                    <a href="{{ route('subscriptions.show', [journal.bill_id]) }}" title="{{ journal.bill_name }}">{{ journal.bill_name }}</a>
                @endif
            </td>

            <!-- new optional fields (2x) -->
            <td class="hide-create_date">
                {{ journal.created_at.isoFormat($dateTimeFormat) }}
            </td>

            <td class="hide-update_date">
                {{ journal.updated_at.isoFormat($dateTimeFormat) }}
            </td>
            <td class="hide-notes">
                {{ journal.notes|default('')|markdown }}
            </td>

            <!-- more new dates -->
            <td class="hide-interest_date">
                {% if null != journal.interest_date %}
                    {{ journal.interest_date.isoFormat($monthAndDayFormat) }}
                @endif
            </td>
            <td class="hide-book_date">
                {% if null != journal.book_date %}
                    {{ journal.book_date.isoFormat($monthAndDayFormat) }}
                @endif
            </td>
            <td class="hide-process_date">
                {% if null != journal.process_date %}
                    {{ journal.process_date.isoFormat($monthAndDayFormat) }}
                @endif
            </td>
            <td class="hide-due_date">
                {% if null != journal.due_date %}
                    {{ journal.due_date.isoFormat($monthAndDayFormat) }}
                @endif
            </td>
            <td class="hide-payment_date">
                {% if null != journal.payment_date %}
                    {{ journal.payment_date.isoFormat($monthAndDayFormat) }}
                @endif
            </td>
            <td class="hide-invoice_date">
                {% if null != journal.invoice_date %}
                    {{ journal.invoice_date.isoFormat($monthAndDayFormat) }}
                @endif
            </td>
        </tr>

    @endforeach

    </tbody>
</table>
