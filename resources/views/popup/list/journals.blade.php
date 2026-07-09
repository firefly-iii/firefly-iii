    <table class="table table-hover table-sm">
    <thead>
    <tr>
        <th>&nbsp;</th>
        <th>{{ trans('list.description') }}</th>
        <th>{{ trans('list.amount') }}</th>
        <th class="hidden-sm hidden-xs">{{ trans('list.date') }}</th>
        @if(!$hideSource)
            <th class="hidden-xs">{{ trans('list.from') }}</th>
        @endif
        @if(!$hideDestination)
            <th class="hidden-xs">{{ trans('list.to') }}</th>
        @endif

        {{-- Hide budgets? --}}
        @if(!$hideBudget)
            <th class="hidden-xs">
                <span class="bi bi-pie-chart" title="{{ trans('list.budget') }}"></span>
            </th>
        @endif

        {{-- Hide categories? --}}
        @if(!$hideCategory)
            <th class="hidden-xs">
                <span class="bi bi-bookmark" title="{{ trans('list.category') }}"></span>
            </th>
        @endif
    </tr>
    </thead>
    <tbody>
    {{-- Make sum: --}}
    @php
    $sum = 0;
    $symbol = '';
    $decimal_places = 2;
    @endphp
    @foreach($journals as $transaction)
        {{-- add to sum --}}
        @php
        $symbol = $transaction['currency_symbol'];
        $decimal_places = $transaction['currency_decimal_places'];
        @endphp
        <tr class="drag" data-date="{{ $transaction['date']->format('Y-m-d') }}" data-id="{{ $transaction['journal_id'] }}">
            <td class="hidden-xs">
                <x-elements.transaction-type-icon type="$transaction['transaction_type_type']" />
            </td>

            <td>
                <a href="{{ route('transactions.show',$transaction['transaction_group_id']) }}">
                    @if(strlen($transaction['group_title']) > 0)
                        {{ $transaction['group_title'] }} ({{ $transaction['description'] }})
                    @else
                        {{ $transaction['description'] }}
                    @endif
                </a>
            </td>

            <td>
                @if('Deposit' == $transaction['transaction_type_type'])
                    {!! format_amount_by_symbol($transaction['amount']*-1, $transaction['currency_symbol'], $transaction['currency_decimal_places']) !!}
                    @if(null !== $transaction['foreign_amount'])
                        ({!! format_amount_by_symbol($transaction['foreign_amount']*-1, $transaction['foreign_currency_symbol'], $transaction['foreign_currency_decimal_places']) !!})
                    @endif
                @php
                $sum = $sum + ($transaction['amount']*-1);
                @endphp
                @elseif('Transfer' === $transaction['transaction_type_type'])
                    <span class="text-info money-transfer">
                        {!! format_amount_by_symbol($transaction['amount']*-1, $transaction['currency_symbol'], $transaction['currency_decimal_places'], false) !!}
                        @if(null !== $transaction['foreign_amount'])
                            ({!! format_amount_by_symbol($transaction['foreign_amount']*-1, $transaction['foreign_currency_symbol'], $transaction['foreign_currency_decimal_places'], false) !!})
                        @endif
                            </span>
                        @php
                            $sum = $sum + ($transaction['amount']*-1);
                        @endphp
                @else
                    {!! format_amount_by_symbol($transaction['amount'], $transaction['currency_symbol'], $transaction['currency_decimal_places']) !!}
                    @if(null !== $transaction['foreign_amount'])
                        ({!! format_amount_by_symbol($transaction['foreign_amount'], $transaction['foreign_currency_symbol'], $transaction['foreign_currency_decimal_places']) !!})
                    @endif
                        @php
                        $sum = $sum + $transaction['amount'];
                        @endphp
                @endif

            </td>

            <td class="hidden-sm hidden-xs">
                {{ $transaction['date']->isoFormat($monthAndDayFormat) }}
            </td>

            @if(!$hideSource)
                <td class="hidden-xs">
                    <a href="{{ route('accounts.show', $transaction['source_account_id']) }}">
                        {{ $transaction['source_account_name'] }}
                    </a>
                </td>
            @endif

            @if(!$hideDestination)
                <td class="hidden-xs">
                    <a href="{{ route('accounts.show', $transaction['destination_account_id']) }}">
                        {{ $transaction['destination_account_name'] }}
                    </a>
                </td>
            @endif

            <!-- Do NOT hide the budget? -->
            @if(!$hideBudget)
                <td class="hidden-xs">
                    @if(null !== $transaction['budget_id'])
                        <a href="{{ route('budgets.show', [$transaction['budget_id']]) }}">
                            {{ $transaction['budget_name'] }}
                        </a>
                    @endif
                </td>
            @endif

            <!-- Do NOT hide the category? -->
            @if(!$hideCategory)
                <td class="hidden-xs">
                    @if(null !== $transaction['category_id'])
                        <a href="{{ route('categories.show', [$transaction['category_id']]) }}">
                            {{ $transaction['category_name'] }}
                        </a>
                    @endif
                </td>
            @endif
        </tr>
    @endforeach
    </tbody>
    <tfoot>
    <tr>
        <td colspan="2" class="text-end"><em>{{ __('firefly.sum') }}:</em></td>
        <td>
            @if($sum !== 0)
                {!! format_amount_by_symbol($sum, $symbol, $decimal_places) !!}
            @endif
        </td>
    </tr>
    </tfoot>
</table>
