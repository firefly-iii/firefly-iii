<table class="table table-striped table-sm">
    <thead>
    <tr class="ignore">
        <th class="hidden-xs" colspan="2">&nbsp;</th>
        <th>{{ trans('list.description') }}</th>
        <th class="text-right">{{ trans('list.amount') }}</th>
        <th class="hidden-xs hidden-sm hidden-md">{{ trans('list.reconcile') }}</th>
        <th class="hidden-xs hidden-sm">{{ trans('list.date') }}</th>
        <th class="hidden-xs hidden-sm hidden-md">{{ trans('list.from') }}</th>
        <th class="hidden-xs hidden-sm hidden-md">{{ trans('list.to') }}</th>
    </tr>
    </thead>
    <tbody>
    {{-- data for previous/next markers --}}
    @php
    $endSet = false;
    $startSet = false;
    @endphp
    @foreach($journals as $journal)
        {{-- start marker --}}
        @if($journal['date'] < $start && false === $startSet)
            <tr>
                <td colspan="4">
                    &nbsp;
                </td>
                <td>
                    <input type="checkbox" class="check_all_btn">
                </td>
                <td colspan="3">
                    <span class="badge text-bg-primary">
                        {{ trans('firefly.start_of_reconcile_period', ['period' => $start->isoFormat($monthAndDayFormat)]) }}
                    </span>
                </td>
                <td colspan="2">
                    &nbsp;
                </td>
            </tr>
            @php
            $startSet = true;
            @endphp
        @endif

        {{-- end marker --}}
        @if($journal['date'] <= $end && false === $endSet)
            <tr>
                <td colspan="4">
                    &nbsp;
                </td>
                <td>
                    <input type="checkbox" class="check_all_btn">
                </td>
                <td colspan="3">
                    <span class="badge text-bg-primary">
                        {{ trans('firefly.end_of_reconcile_period', ['period' => $end->isoFormat($monthAndDayFormat)]) }}
                    </span>
                </td>
                <td colspan="2">
                    &nbsp;
                </td>
            </tr>
            @php
                $endSet = true;
            @endphp
        @endif
        <tr data-date="{{ $journal['date']->format('Y-m-d') }}" data-id="{{ $journal['transaction_journal_id'] }}">
            <td class="hidden-xs">
                <div class="btn-group btn-group-sm">
                    <a href="{{ route('transactions.edit', [$journal['transaction_group_id']]) }}" class="btn btn-xs btn-outline-secondary"><span
                                class="bi bi-pencil"></span></a>
                    <a href="{{ route('transactions.delete', [$journal['transaction_group_id']]) }}" class="btn btn-danger"><span
                                class="bi bi-trash"></span></a>
                </div>

            </td>

            <!-- icon -->
            <td class="hidden-xs">
                <x-elements.transaction-type-icon :type="$journal['transaction_type_type']" />
            </td>

            <!-- description -->
            <td>
                <a href="{{ route('transactions.show', [$journal['transaction_group_id']]) }}" title="{{ $journal['description'] }}">
                    @if($journal['group_title'])
                        <span class="text-muted"><span class="bi bi-share-fill" aria-hidden="true"></span></span> {{ $journal['group_title'] }}:
                    @endif
                    {{ $journal['description'] }}</a>
            </td>

            <td class="text-right">
                <span class="mr-1">
                    {!! format_amount_by_symbol($journal['amount'], $journal['currency_symbol'], $journal['currency_decimal_places'])  !!}
                @if(null !== $journal['foreign_amount'])
                    ({!! format_amount_by_symbol($journal['foreign_amount'], $journal['foreign_currency_symbol'], $journal['foreign_currency_decimal_places'])  !!})
                @endif
                </span>
            </td>

            <td>
                @if($journal['date'] >= $start && $journal['date'] <= $end)
                    @if($journal['reconciled'])
                        <span class="bi bi-check" aria-hidden="true"></span>
                        <input type="hidden" name="cleared[]" data-younger="false" {{-- @if($journal['date'] < $start)true@else false@endif --}}
                        data-inrange="true" {{-- {% if journal.date >= start and journal.date <= end  %}true@elsefalse"@endif --}}
                        class="cleared" data-id="{{ $journal['transaction_journal_id'] }}" value="{{ $journal['amount'] }}">
                    @else
                        <input type="checkbox" name="reconciled[]"
                               data-younger="false" {{-- {% if journal.date < start %}true@elsefalse@endif --}}
                               data-inrange="true" {{-- {% if journal.date >= start and journal.date <= end  %}true@elsefalse"@endif --}}
                        @if($currency->id === $journal['currency_id']) value="{{ $journal['amount'] }}" @endif
                        @if($currency->id === $journal['foreign_currency_id']) value="{{ $journal['foreign_amount'] }}"@endif
                         data-id="{{ $journal['transaction_journal_id'] }}" disabled class="reconcile_checkbox">
                    @endif
                @else
                    <!-- if not in range, just show reconciliation status -->
                    @if($journal['reconciled'])
                        <span class="bi bi-check" aria-hidden="true"></span>
                    @endif

                @endif

            </td>

            <td class="hidden-sm hidden-xs">
                {{ $journal['date']->isoFormat($monthAndDayFormat) }}
            </td>

            <td class="hidden-xs hidden-sm hidden-md">
                <a href="{{ route('accounts.show', [$journal['source_account_id']]) }}" title="{{ $journal['source_account_iban'] ?? $journal['source_account_name'] }}">{{ $journal['source_account_name'] }}</a>
            </td>

            <td class="hidden-xs hidden-sm hidden-md">
                <a href="{{ route('accounts.show', [$journal['destination_account_id']]) }}" title="{{ $journal['destination_account_iban'] ?? $journal['destination_account_name'] }}">{{ $journal['destination_account_name'] }}</a>
            </td>
        </tr>
    @endforeach

    {{--  if the start marker has not been generated yet, do it now, at the end of the loop. --}}
    @if(false === $startSet)
        <tr>
            <td colspan="5">
                &nbsp;
            </td>
            <td colspan="3">
                <span class="badge text-bg-primary">
                    {{ trans('firefly.start_of_reconcile_period', ['period' => $start->isoFormat($monthAndDayFormat)]) }}
                </span>
            </td>
            <td colspan="2">
                &nbsp;
            </td>
        </tr>
        @php
            $startSet = true;
        @endphp
    @endif
    </tbody>
</table>
