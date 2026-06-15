<table class="table table-condensed table-hover table-responsive">
    <thead>
    <tr>
        @if($showCategory || ($showBudget ?? false))
        <td colspan="7" class="no-margin-pagination">{{ $groups->links('pagination.bootstrap-4') }}</td>
        @else
        <td colspan="6" class="no-margin-pagination">{{ $groups->links('pagination.bootstrap-4') }}</td>
        @endif
        <td colspan="1" class="d-xs-none">
            <!-- Single button -->
            <div class="btn-group btn-group-sm action-menu pull-right hidden">
                <button type="button" class="btn btn-secondary btn-sm dropdown-toggle" data-toggle="dropdown"
                        aria-haspopup="true" aria-expanded="false">
                    {{ __('firefly.actions') }}<span class="caret"></span>
                </button>
                <ul class="dropdown-menu dropdown-menu-right">
                    <li><a href="#" class="mass-edit"><span class="fa fa-fw fa-pencil"></span>
                            <span class="txt">{{ __('firefly.mass_edit') }}</span></a></li>
                    <li><a href="#" class="bulk-edit"><span class="fa fa-fw fa-pencil-square-o"></span>
                            <span class="txt">{{ __('firefly.bulk_edit') }}</span></a></li>
                    <li><a href="#" class="mass-delete"><span class="fa fa-fw fa-trash"></span>
                            <span class="txt">{{ __('firefly.mass_delete') }}</span></a></li>
                </ul>
            </div>
        </td>
        <td colspan="1" class="d-xs-none">
            <div class="pull-right">
                <input id="list_ALL" value="1" name="select-all" type="checkbox" class="select-all form-check-inline"/>
            </div>
        </td>
    </tr>
    <tr>
        <th class="d-xs-none">&nbsp;</th>
        <th>{{ trans('list.description') }}</th>
        <th class="text-end">{{ trans('list.amount') }}</th>
        @if(\FireflyIII\Support\Facades\AppConfiguration::get('use_running_balance', true))
            <th class="text-end">{{ trans('list.running_balance') }}</th>
        @endif
        <th>{{ trans('list.date') }}</th>
        <th>{{ trans('list.source_account') }}</th>
        <th>{{ trans('list.destination_account') }}</th>
        @if($showCategory)
        <th class="d-xs-none">{{ trans('list.category') }}</th>
        @endif
        @if($showBudget ?? false)
        <th class="d-xs-none">{{ trans('list.budget') }}</th>
        @endif
        <th class="d-xs-none">&nbsp;</th><!-- actions -->
        <th class="d-xs-none">&nbsp;</th><!-- checkbox -->
    </tr>
    </thead>
    <tbody>
    @foreach($groups as $group)
    @if($group->count > 1)
    <tr class="top-light-border">
        <td colspan="2" class="top-light-border">
            <small><strong>
                    <a href="{{ route('transactions.show', [$group->id]) }}"
                       title="{{ $group->title }}">{{ $group->title }}</a>
                </strong></small>
        </td>
        <td colspan="1" class="text-end top-light-border">
            @foreach($group['sums'] as $sum)
            @if('Deposit' === $group['transaction_type'])
                    {{ formatAmountBySymbol($sum['amount']*-1, $sum['currency_symbol'], $sum['currency_decimal_places']) }}
            @if($convertToPrimary && 0 !== $sum['pc_amount'])
            (~ {{ formatAmountBySymbol($sum['pc_amount']*-1, $primaryCurrency->symbol, $primaryCurrency->decimal_places) }})
            @endif
            @if($loop->index !== count($group['sums'])),@endif
            @elseif('Transfer' === $group['transaction_type'])
            <span class="text-info money-transfer">
                            {{ formatAmountBySymbol($sum['amount']*-1, $sum['currency_symbol'], $sum['currency_decimal_places'], false) }}
                @if($convertToPrimary && 0 !== $sum['pc_amount'])
                                    (~ {{ formatAmountBySymbol($sum['pc_amount']*-1, $primaryCurrency->symbol, $primaryCurrency->decimal_places) }})
                                @endif
                @if($loop->index !== count($group['sums'])),@endif
                            </span>
            @else
                {{ formatAmountBySymbol($sum['amount'], $sum['currency_symbol'], $sum['currency_decimal_places']) }}
                    @if($convertToPrimary && 0 !== $sum['pc_amount'])
            (~ {{ formatAmountBySymbol($sum['pc_amount']*-1, $primaryCurrency->symbol, $primaryCurrency->decimal_places) }})
            @endif
                @if($loop->index !== count($group['sums'])),@endif
            @endif
            @endforeach
        </td>
        <!-- column to span accounts + extra fields -->
        @if($showCategory || $showBudget ?? false)
        <td class="top-light-border" colspan="3">&nbsp;</td>
        @else
        <td class="top-light-border" colspan="2">&nbsp;</td>
        @endif
        <td class="top-light-border d-xs-none" colspan="2">
            <div class="btn-group btn-group-sm pull-right">
                <button type="button" class="btn btn-secondary dropdown-toggle" data-toggle="dropdown"
                        aria-haspopup="true" aria-expanded="false">
                    {{ __('firefly.actions') }} <span class="caret"></span></button>
                <ul class="dropdown-menu dropdown-menu-right" role="menu">
                    <li><a href="{{ route('transactions.edit', [$group->id]) }}"><span
                                class="fa fa-fw fa-pencil"></span> {{ __('firefly.edit') }}</a></li>
                    <li><a href="{{ route('transactions.delete', [$group->id]) }}"><span
                                class="fa fa-fw fa-trash"></span> {{ __('firefly.delete') }}</a></li>
                    <li><a href="#" data-id="{{ $group->id }}" class="clone-transaction"><span
                                class="fa fa-copy fa-fw"></span> {{ __('firefly.clone') }}</a></li>
                    <li><a href="#" data-id="{{ $group->id }}" class="clone-transaction-and-edit"><span
                                class="fa fa-copy fa-fw"></span> {{ __('firefly.clone_and_edit') }}</a></li>
                </ul>
            </div>
        </td>
        <td class="top-light-border d-xs-none">&nbsp;</td>
    </tr>
    @endif
    @foreach($group['transactions'] as $index => $transaction)
        @php $className = ''; @endphp
        @if(count($group['transactions']) === $loop->index && $group['count'] > 1)
            @php $className = 'bottom-light-border'; @endphp
       @endif
    <tr data-date="{{ $transaction['date']->format('Y-m-d') }}" data-count="{{ $group['count'] }}" data-id="{{ $group->id }}">
        <td class="d-xs-none {{ $className }}">
            <!-- TODO icon helper -->
            @if('Withdrawal' === $transaction['transaction_type_type'])
            <span class="fa fa-long-arrow-left fa-fw"
                  title="{{ trans('firefly.Withdrawal') }}"></span>
            @endif

            @if('Deposit' === $transaction['transaction_type_type'])
            <span class="fa fa-long-arrow-right fa-fw"
                  title="{{ trans('firefly.Deposit') }}"></span>
            @endif

            @if('Transfer' === $transaction['transaction_type_type'])
            <span class="fa fa-exchange fa-fw" title="{{ trans('firefly.Transfer') }}"></span>
            @endif

            @if('Reconciliation' === $transaction['transaction_type_type'])
            <span class="fa-fw fa fa-calculator"
                  title="{{ trans('firefly.reconciliation_transaction') }}"></span>
            @endif
            @if('Opening balance' === $transaction['transaction_type_type'])
            <span class="fa-fw fa fa-star-o" title="{{ trans('firefly.Opening balance') }}"></span>
            @endif
            @if('Liability credit' === $transaction['transaction_type_type'])
            <span class="fa-fw fa fa-star-o"
                  title="{{ trans('firefly.Liability credit') }}"></span>
            @endif
        </td>
        <td class="{{ $className }}">
            @if($transaction['reconciled'])
            <span class="fa fa-check"></span>
            @endif
            @if(count($transaction['attachments']) > 0)
            <span class="fa fa-paperclip"></span>
            @endif
            @if(1 === $group['count'])
                <a href="{{ route('transactions.show', [$group->id]) }}" title="{{ $transaction['description'] }}">
            @endif
                    {{ $transaction['description'] }}
                    @if(1 === $group['count'])
            </a>
            @endif
        </td>
        <td class="{{ $className }} text-end">
            <!-- TODO amount display helper -->
            {{-- deposit --}}
            @if('Deposit' === $transaction['transaction_type_type'])
                {{-- amount of deposit --}}
                {{ formatAmountBySymbol($transaction['amount']*-1, $transaction['currency_symbol'], $transaction['currency_decimal_places']) }}
                {{-- foreign amount of deposit --}}
                @if(null !== $transaction['foreign_amount'])
                    ({{ formatAmountBySymbol($transaction['foreign_amount']*-1, $transaction['foreign_currency_symbol'], $transaction['foreign_currency_decimal_places']) }})
                @endif
                {{--  primary currency amount of deposit --}}
                @if($convertToPrimary && 0 != $transaction['pc_amount'])
                    (~ {{ formatAmountBySymbol($transaction['pc_amount']*-1, $primaryCurrency->symbol, $primaryCurrency->decimal_places) }})
               @endif
            {{-- transfer --}}
            @elseif('Transfer' === $transaction['transaction_type_type'])
                {{-- amount of transfer --}}
                <span class="text-info money-transfer">
                    {{-- present as negative. --}}
                    @if($transaction['source_account_id'] === $account?->id)
                        neg {{ formatAmountBySymbol($transaction['amount'], $transaction['currency_symbol'], $transaction['currency_decimal_places'], false) }}
                    @endif
                    {{-- present as positive --}}
                    @if($transaction['source_account_id'] !== $account?->id)
                        {{ formatAmountBySymbol($transaction['amount']*-1, $transaction['currency_symbol'], $transaction['currency_decimal_places'], false) }}
                    @endif
                    {{-- foreign amount of transfer (negative) --}}
                    @if(null !== $transaction['foreign_amount'] && $transaction['source_account_id'] === $account?->id)
                        neg ({{ formatAmountBySymbol($transaction['foreign_amount'], $transaction['foreign_currency_symbol'], $transaction['foreign_currency_decimal_places'], false) }})
                    @endif
                    {{-- foreign amount of transfer (positive) --}}
                    @if(null !== $transaction['foreign_amount'] && $transaction['source_account_id'] !== $account?->id)
                        ({{ formatAmountBySymbol($transaction['foreign_amount']*-1, $transaction['foreign_currency_symbol'], $transaction['foreign_currency_decimal_places'], false) }})
                    @endif
                    {{-- transfer in primary currency. Does not care about direction. --}}
                    @if($convertToPrimary && 0 !== $transaction['pc_amount'])
                        (~ {{ formatAmountBySymbol($transaction['pc_amount']*-1, $primaryCurrency->symbol, $primaryCurrency->decimal_places) }})
                    @endif
                </span>
            {{-- opening balance --}}
            @elseif('Opening balance' === $transaction['transaction_type_type'])
                {{-- Is a positive opening balance, present as positive. --}}
                @if('Initial balance account' === $transaction['source_account_type'])
                    {{ formatAmountBySymbol($transaction['amount']*-1, $transaction['currency_symbol'], $transaction['currency_decimal_places']) }}
                    {{-- opening balance may have foreign amount (also pos) --}}
                    @if(null !== $transaction['foreign_amount'])
                        ({{ formatAmountBySymbol($transaction['foreign_amount']*-1, $transaction['foreign_currency_symbol'], $transaction['foreign_currency_decimal_places']) }})
                    @endif
                    {{-- possibly, primary amount. --}}
                    @if($convertToPrimary && 0 !== $transaction['pc_amount'])
                        (~ {{ formatAmountBySymbol($transaction['pc_amount']*-1, $primaryCurrency->symbol, $primaryCurrency->decimal_places) }})
                    @endif
                @else
                    {{-- withdrawal but also any other transaction type: --}}
                    {{ formatAmountBySymbol($transaction['amount'], $transaction['currency_symbol'], $transaction['currency_decimal_places']) }}
                    @if(null !== $transaction['foreign_amount'])
                        ({{ formatAmountBySymbol($transaction['foreign_amount'], $transaction['foreign_currency_symbol'], $transaction['foreign_currency_decimal_places']) }})
                    @endif
                    @if($convertToPrimary && 0 !== $transaction['pc_amount'])
                        (~ {{ formatAmountBySymbol($transaction['pc_amount'], $primaryCurrency->symbol, $primaryCurrency->decimal_places) }})
                  @endif
               @endif
            @elseif('Reconciliation' === $transaction['transaction_type_type'])
                {{-- Reconciliation positive--}}
                @if('Reconciliation account' === $transaction['source_account_type'])
                    {{-- amount, also foreign and converted. --}}
                    {{ formatAmountBySymbol($transaction['amount']*-1, $transaction['currency_symbol'], $transaction['currency_decimal_places']) }}
                    @if(null !== $transaction['foreign_amount'])
                        ({{ formatAmountBySymbol($transaction['foreign_amount']*-1, $transaction['foreign_currency_symbol'], $transaction['foreign_currency_decimal_places']) }})
                   @endif
                    @if($convertToPrimary && 0 !== $transaction['pc_amount'])
                        (~ {{ formatAmountBySymbol($transaction['pc_amount']*-1, $primaryCurrency->symbol, $primaryCurrency->decimal_places) }})
                  @endif
                @else
                    {{-- Reconciliation negative --}}
                    {{ formatAmountBySymbol($transaction['amount'], $transaction['currency_symbol'], $transaction['currency_decimal_places']) }}
                    @if(null !== $transaction['foreign_amount'])
                        ({{ formatAmountBySymbol($transaction['foreign_amount'], $transaction['foreign_currency_symbol'], $transaction['foreign_currency_decimal_places']) }})
                    @endif
                    @if($convertToPrimary && 0 !== $transaction['pc_amount'])
                        (~ {{ formatAmountBySymbol($transaction['pc_amount'], $primaryCurrency->symbol, $primaryCurrency->decimal_places) }})
                    @endif
               @endif
            @elseif('Liability credit' === $transaction['transaction_type_type'])
                {{-- liability credit positive--}}
                @if('Liability credit' === $transaction['source_account_type'])
                    {{ formatAmountBySymbol($transaction['amount'], $transaction['currency_symbol'], $transaction['currency_decimal_places']) }}
                    @if(null !== $transaction['foreign_amount'])
                        ({{ formatAmountBySymbol($transaction['foreign_amount'], $transaction['foreign_currency_symbol'], $transaction['foreign_currency_decimal_places']) }})
                   @endif
                    @if($convertToPrimary && 0 !== $transaction['pc_amount'])
                        (~ {{ formatAmountBySymbol($transaction['pc_amount'], $primaryCurrency->symbol, $primaryCurrency->decimal_places) }})
                   @endif
                @else
                    {{ formatAmountBySymbol($transaction['amount']*-1, $transaction['currency_symbol'], $transaction['currency_decimal_places']) }}
                    @if(null !== $transaction['foreign_amount'])
                    ({{ formatAmountBySymbol($transaction['foreign_amount']*-1, $transaction['foreign_currency_symbol'], $transaction['foreign_currency_decimal_places']) }})
               @endif
                @if($convertToPrimary && 0 !== $transaction['pc_amount'])
                    (~ {{ formatAmountBySymbol($transaction['pc_amount']*-1, $primaryCurrency->symbol, $primaryCurrency->decimal_places) }})
               @endif
            @endif
            @else
                {{--  THE REST most likely, withdrawal but also any other transaction type: --}}
                {{ formatAmountBySymbol($transaction['amount'], $transaction['currency_symbol'], $transaction['currency_decimal_places']) }}
                {{-- foreign amount of withdrawal --}}
                @if(null !== $transaction['foreign_amount'])
                    ({{ formatAmountBySymbol($transaction['foreign_amount'], $transaction['foreign_currency_symbol'], $transaction['foreign_currency_decimal_places']) }})
                @endif
                {{--  primary currency amount of withdrawal, if not in foreign currency --}}
                @if($convertToPrimary && 0 !== $transaction['pc_amount'] && $primaryCurrency->id !== $transaction['foreign_currency_id'])
                    (~ {{ formatAmountBySymbol($transaction['pc_amount'], $primaryCurrency->symbol, $primaryCurrency->decimal_places) }})
                @endif
            @endif
        </td>
        @if(\FireflyIII\Support\Facades\AppConfiguration::get('use_running_balance', true))
        <td class=" {{ $className }} text-end">
            {{-- RUNNING BALANCE --}}
            @if((null === $transaction['balance_dirty'] || false === $transaction['balance_dirty']) && null !== $transaction['destination_balance_after'] && null !== $transaction['source_balance_after'])
                @if('Deposit' === $transaction['transaction_type_type'])
                    @if($transaction['source_account_id'] == $account?->id)
                        <span title="Deposit, source">{{ formatAmountBySymbol($transaction['source_balance_after'], $transaction['currency_symbol'], $transaction['currency_decimal_places']) }}</span>
                    @else
                    @if('Revenue account' === $transaction['source_account_type'])
                        <span title="Deposit from revenue">{{ formatAmountBySymbol($transaction['destination_balance_after'], $transaction['currency_symbol'], $transaction['currency_decimal_places']) }}</span>
                    @else
                        <span title="Deposit from liab">{{ formatAmountBySymbol($transaction['destination_balance_after'], $transaction['foreign_currency_symbol'], $transaction['foreign_currency_decimal_places']) }}</span>
                    @endif
                    {{-- if this is a deposit from revenue account, use the destination account currency? For #12043 and #12169. Otherwise, keep at source account -}}
                    {{-- changed from normal currency_symbol to foreign_currency_symbol for #12043 --}}
                @endif
            @elseif('Withdrawal' === $transaction['transaction_type_type'])
                {{-- withdrawal into a liability --}}
                @if(in_array($transaction['destination_account_type'], ['Mortgage','Debt','Loan'], true))
                    @if($currency['id'] === $transaction['currency_id'])
                        @if($account?->id === $transaction['source_account_id'])
                            <span title="Withdrawal, liab, source">{{ formatAmountBySymbol($transaction['source_balance_after'], $transaction['currency_symbol'], $transaction['currency_decimal_places']) }}</span>
                        @elseif($account?->id === $transaction['destination_account_id'])
                            <span title="Withdrawal, liab, dest">{{ formatAmountBySymbol($transaction['destination_balance_after'], $transaction['currency_symbol'], $transaction['currency_decimal_places']) }}</span>
                        @else
                            -
                        @endif
                    @endif
                    @if($currency['id'] === $transaction['foreign_currency_id'] && null !== $transaction['destination_balance_after'] && null !== $transaction['destination_balance_after'])
                        <span title="Withdrawal, liab, dest 2">{{ formatAmountBySymbol($transaction['destination_balance_after'], $transaction['foreign_currency_symbol'] ?? $transaction['currency_symbol'], $transaction['foreign_currency_decimal_places'] ?? $transaction['currency_decimal_places']) }}</span>
                    @endif
                {{-- withdrawal into an expense account --}}
                @else
                    @if($account?->id === $transaction['source_account_id'])
                        <span title="Withdrawal, source">{{ formatAmountBySymbol($transaction['source_balance_after'], $transaction['currency_symbol'], $transaction['currency_decimal_places']) }}</span>
                    @elseif($account?->id === $transaction['destination_account_id'])
                        <span title="Withdrawal, dest">{{ formatAmountBySymbol($transaction['destination_balance_after'], $transaction['currency_symbol'], $transaction['currency_decimal_places']) }}</span>
                    @else
                        -
                    @endif
               @endif
            @elseif('Opening balance' === $transaction['transaction_type_type'])
                @if($account?->id == $transaction['source_account_id'])
                    <span title="Opening balance, src">{{ formatAmountBySymbol($transaction['source_balance_after'], $transaction['currency_symbol'], $transaction['currency_decimal_places']) }}</span>
                @elseif($account?->id == $transaction['destination_account_id'])
                    <span title="Opening balance, dest">{{ formatAmountBySymbol($transaction['destination_balance_after'], $transaction['currency_symbol'], $transaction['currency_decimal_places']) }}</span>
                @else
                -
                @endif
            @elseif('Transfer' === $transaction['transaction_type_type'])
                @if($account?->id == $transaction['source_account_id'])
                    <span title="Transfer, source">{{ formatAmountBySymbol($transaction['source_balance_after'], $transaction['currency_symbol'], $transaction['currency_decimal_places']) }}</span>
                @else
                    @if(null === $transaction['foreign_currency_id'])
                        <span title="Transfer, dest, normal currency">{{ formatAmountBySymbol($transaction['destination_balance_after'], $transaction['currency_symbol'], $transaction['currency_decimal_places']) }}</span>
                    @endif
                    @if(null !== $transaction['foreign_currency_id'])
                        <span title="Transfer, dest, foreign currency">{{ formatAmountBySymbol($transaction['destination_balance_after'], $transaction['foreign_currency_symbol'], $transaction['foreign_currency_decimal_places']) }}</span>
                    @endif
                @endif
            @else
            &nbsp;
            @endif
        @endif
        </td>
        @endif
        <td class="{{ $className }}">
            {{ $transaction['date']->isoFormat($monthAndDayFormat) }}
        </td>
        <td class="{{ $className }}">
            @if('Cash account' === $transaction['source_account_type'])
                <span class="text-success">({{ __('firefly.cash') }})</span>
            @else
            <a href="{{ route('accounts.show', [$transaction['source_account_id'] ?? 1]) }}"
               title="{{ $transaction['source_account_iban'] ?? $transaction['source_account_name'] }}">{{ $transaction['source_account_name'] }}</a>
            @endif
        </td>
        <td class="{{ $className }}">
            @if('Cash account' == $transaction['destination_account_type'])
                <span class="text-success">({{ __('firefly.cash') }})</span>
            @else
                <a href="{{ route('accounts.show', [$transaction['destination_account_id'] ?? 1]) }}" title="{{ $transaction['destination_account_iban'] ?? $transaction['destination_account_name'] }}">{{ $transaction['destination_account_name'] }}</a>
            @endif
        </td>
        @if($showCategory)
        <td class="d-xs-none {{ $className }}">
            @if(null !== $transaction['category_id'])
            <a href="{{ route('categories.show', [$transaction['category_id']]) }}"
               title="{{ $transaction['category_name'] }}">{{ $transaction['category_name'] }}</a>
            @endif
        </td>
        @endif
        @if($showBudget)
        <td class="d-xs-none {{ $className }}">
            @if(null !== $transaction['budget_id'])
            <a href="{{ route('budgets.show', [$transaction['budget_id']]) }}"
               title="{{ $transaction['budget_name'] }}">{{ $transaction['budget_name'] }}</a>
            @endif
        </td>
        @endif

        @if(1 === count($group))
        <td class="d-xs-none {{ $className }}">
            <div class="btn-group btn-group-sm pull-right">
                <button type="button" class="btn btn-secondary dropdown-toggle" data-toggle="dropdown"
                        aria-haspopup="true" aria-expanded="false">
                    {{ __('firefly.actions') }} <span class="caret"></span></button>
                <ul class="dropdown-menu dropdown-menu-right" role="menu">
                    <li><a href="{{ route('transactions.edit', [$group->id]) }}"><span class="fa fa-fw fa-pencil"></span> {{ __('firefly.edit') }}</a></li>
                    @if($transaction['transaction_type_type'] !== 'Opening balance' && $transaction['transaction_type_type'] !== 'Liability credit')
                        <li><a href="{{ route('transactions.delete', [$group->id]) }}"><span class="fa fa-fw fa-trash"></span> {{ __('firefly.delete') }}</a></li>
                    @endif
                    @if($transaction['transaction_type_type'] !== 'Reconciliation' and $transaction['transaction_type_type'] !== 'Opening balance' and $transaction['transaction_type_type'] !== 'Liability credit')
                        <li><a href="#" data-id="{{ $group->id }}" class="clone-transaction"><span class="fa fa-copy fa-fw"></span> {{ __('firefly.clone') }}</a></li>
                        <li><a href="#" data-id="{{ $group->id }}" class="clone-transaction-and-edit"><span class="fa fa-copy fa-fw"></span> {{ __('firefly.clone_and_edit') }}</a></li>
                        <li><a href="{{ route('rules.create-from-journal', [$transaction['transaction_journal_id']]) }}"><span class="fa fa-fw fa-random"></span> {{ __('firefly.create_rule_from_transaction') }}</a></li>
                    @endif
                </ul>
            </div>
        </td>

        @endif
        @if(1 !== count($group))
        <td class="d-xs-none {{ $className }}">
            &nbsp;
        </td>
        @endif
        <td class="d-xs-none {{ $className }}">
            @if($transaction['transaction_type_type'] !== 'Reconciliation' and $transaction['transaction_type_type'] !== 'Opening balance' and $transaction['transaction_type_type'] !== 'Liability credit')
            <div class="pull-right">
                <input id="list_{{ $transaction['transaction_journal_id'] }}"
                       value="{{ $transaction['transaction_journal_id'] }}"
                       name="journals[{{ $transaction['transaction_journal_id'] }}]"
                       type="checkbox" class="mass-select form-check-inline"
                       data-value="{{ $transaction['transaction_journal_id'] }}"/>
            @endif
            </div>
        </td>
    </tr>
    @endforeach
    @endforeach
    </tbody>
    <tfoot>
    <tr>
        <td colspan="8">
            <div class="pull-right">
                <!-- Single button -->
                <div class="btn-group action-menu btn-group-sm pull-right hidden">
                    <button type="button" class="btn btn-secondary btn-sm dropdown-toggle" data-toggle="dropdown"
                            aria-haspopup="true" aria-expanded="false">
                        {{ __('firefly.actions') }} <span class="caret"></span>
                    </button>
                    <ul class="dropdown-menu btn-group-sm dropdown-menu-right">
                        <li><a href="#" class="mass-edit"><span class="fa fa-fw fa-pencil"></span>
                                <span>{{ __('firefly.mass_edit') }}</span></a></li>
                        <li><a href="#" class="bulk-edit"><span class="fa fa-fw fa-pencil-square-o"></span>
                                <span>{{ __('firefly.bulk_edit') }}</span></a></li>
                        <li><a href="#" class="mass-delete"><span class="fa fa-fw fa-trash"></span>
                                <span>{{ __('firefly.mass_delete') }}</span></a></li>
                    </ul>
                </div>
            </div>
        </td>
    </tr>
    <tr>
        @if($showCategory || $showBudget)
        <td colspan="9" class="no-margin-pagination">{{ $groups->links('pagination.bootstrap-4') }}</td>
        @else
        <td colspan="8" class="no-margin-pagination">{{ $groups->links('pagination.bootstrap-4') }}</td>
        @endif
    </tr>
    </tfoot>
</table>
