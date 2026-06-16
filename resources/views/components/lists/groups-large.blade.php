<table class="table table-sm">
    <thead>
    <tr>
        @if(($showCategory ?? false) || ($showBudget ?? false))
            <td colspan="8">{{ $groups->links('pagination.bootstrap-4') }}</td>
        @else
            <td colspan="7">{{ $groups->links('pagination.bootstrap-4') }}</td>
        @endif
        <td class="d-xs-none text-end">
            <!-- Single button -->
            <div class="action-menu d-none"> <!-- d-none -->
                <button class="btn btn-sm btn-outline-secondary dropdown-toggle" type="button" id="top_action_menu" data-bs-toggle="dropdown" aria-expanded="false">
                    {{ __('firefly.actions') }}<span class="caret"></span>
                </button>
                <ul class="dropdown-menu" aria-labelledby="top_action_menu">
                    <li><a href="#" class="dropdown-item mass-edit"><span class="fa fa-fw fa-pencil"></span><span class="txt">{{ __('firefly.mass_edit') }}</span></a></li>
                    <li><a href="#" class="dropdown-item bulk-edit"><span class="fa fa-fw fa-pencil-square-o"></span><span class="txt">{{ __('firefly.bulk_edit') }}</span></a></li>
                    <li><a href="#" class="dropdown-item mass-delete"><span class="fa fa-fw fa-trash"></span><span class="txt">{{ __('firefly.mass_delete') }}</span></a></li>
                </ul>
            </div>
        </td>
        <td class="d-xs-none text-end">
            <input id="list_ALL" value="1" name="select-all" type="checkbox" class="select-all form-check-inline"/>
        </td>
    </tr>
    <tr>
        <th class="d-xs-none">&nbsp;</th>
        <th>{{ trans('list.description') }}</th>
        <th class="text-end">{{ trans('list.amount') }}</th>
        @if(getAppConfiguration('use_running_balance', true))
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
        @if($group['count'] > 1)
        <tr class="top-light-border">
            <td colspan="2">
                <small><strong><a href="{{ route('transactions.show', [$group['id']]) }}" title="{{ $group['title'] }}">{{ $group['title'] }}</a></strong></small>
            </td>
            <td colspan="1" class="text-end">
                {{-- Total amount of all journals in the group. --}}
                @foreach($group['sums'] as $sum)
                    @if('Deposit' === $group['transaction_type'])
                        {!! formatAmountBySymbol($sum['amount']*-1, $sum['currency_symbol'], $sum['currency_decimal_places']) !!}
                        @if($convertToPrimary && 0 !== $sum['pc_amount'])
                            (~ {!! formatAmountBySymbol($sum['pc_amount']*-1, $primaryCurrency->symbol, $primaryCurrency->decimal_places) !!})
                        @endif
                        @if($loop->index !== count($group['sums'])-1),@endif
                    @elseif('Transfer' === $group['transaction_type'])
                        <span class="text-info money-transfer">
                        {!! formatAmountBySymbol($sum['amount']*-1, $sum['currency_symbol'], $sum['currency_decimal_places'], false) !!}
                        @if($convertToPrimary && 0 !== $sum['pc_amount'])
                            (~ {!! formatAmountBySymbol($sum['pc_amount']*-1, $primaryCurrency->symbol, $primaryCurrency->decimal_places) !!})
                        @endif
                        @if($loop->index !== count($group['sums'])-1),@endif
                        </span>
                    @else
                        {!! formatAmountBySymbol($sum['amount'], $sum['currency_symbol'], $sum['currency_decimal_places']) !!}
                        @if($convertToPrimary && 0 !== $sum['pc_amount'])
                            (~ {!! formatAmountBySymbol($sum['pc_amount'], $primaryCurrency->symbol, $primaryCurrency->decimal_places) !!})
                         @endif
                        @if($loop->index !== count($group['sums'])-1),@endif
                   @endif
                @endforeach
            </td>
            <!-- column to span accounts + extra fields -->
            @if($showCategory ?? false || $showBudget ?? false)
                <td colspan="5" class="top-light-border">&nbsp;</td>
            @else
                <td colspan="4" class="top-light-border">&nbsp;b</td>
            @endif
        <td class="top-light-border d-xs-none text-end">
            <div class=""> <!-- d-none ? -->
                <button type="button" class="btn btn-sm btn-outline-secondary dropdown-toggle" data-toggle="dropdown"
                        aria-haspopup="true" aria-expanded="false">{{ __('firefly.actions') }} <span class="caret"></span></button>
                <ul class="dropdown-menu dropdown-menu-right" role="menu">
                    <li><a href="{{ route('transactions.edit', [$group['id']]) }}"><span class="fa fa-fw fa-pencil"></span> {{ __('firefly.edit') }}</a></li>
                    <li><a href="{{ route('transactions.delete', [$group['id']]) }}"><span class="fa fa-fw fa-trash"></span> {{ __('firefly.delete') }}</a></li>
                    <li><a href="#" data-id="{{ $group['id'] }}" class="clone-transaction"><span class="fa fa-copy fa-fw"></span> {{ __('firefly.clone') }}</a></li>
                    <li><a href="#" data-id="{{ $group['id'] }}" class="clone-transaction-and-edit"><span class="fa fa-copy fa-fw"></span> {{ __('firefly.clone_and_edit') }}</a></li>
                </ul>
            </div>
        </td>
        <td class="top-light-border d-xs-none">&nbsp;</td><!-- would have checkbox -->
    </tr>
    @endif
    @foreach($group['transactions'] as $index => $transaction)
        @php $className = ''; @endphp
        @if(count($group['transactions']) === $loop->index && $group['count'] > 1)
            @php $className = 'bottom-light-border'; @endphp
       @endif
    <tr data-date="{{ $transaction['date']->format('Y-m-d') }}" data-count="{{ $group['count'] }}" data-id="{{ $group['id'] }}">
        <td class="d-xs-none {{ $className }}">
            <x-elements.transaction-type-icon :type="$transaction['transaction_type_type']" />
        </td>
        <td class="{{ $className }}">
            @if($transaction['reconciled'])
                <span class="bi bi-check"></span>
            @endif
            @if(count($transaction['attachments']) > 0)
                <span class="bi bi-paperclip"></span>
            @endif
            @if(1 === $group['count'])
                <a href="{{ route('transactions.show', [$group['id']]) }}" title="{{ $transaction['description'] }}">
            @endif
                {{ $transaction['description'] }}
            @if(1 === $group['count'])
                </a>
            @endif
        </td>
        <td class="{{ $className }} text-end">
            <x-elements.transaction-amount
                :type="$transaction['transaction_type_type']"
                :amount="['amount' => $transaction['amount'], 'currency_symbol' => $transaction['currency_symbol'], 'currency_decimal_places' => $transaction['currency_decimal_places']]"
                :foreign="['amount' => $transaction['foreign_amount'],'currency_id' => $transaction['foreign_currency_id'], 'currency_symbol' => $transaction['foreign_currency_symbol'], 'currency_decimal_places' => $transaction['foreign_currency_decimal_places']]"
                :account="$account ?? null"
                :pc-amount="$transaction['pc_amount']"
            />
        </td>
        @if(getAppConfiguration('use_running_balance', true))
        <td class=" {{ $className }} text-end">
            <x-elements.transaction-running-balance
                :balance-dirty="$transaction['balance_dirty'] ?? false"
                :currency="[]"
                :foreign="[]"
                :type="$transaction['transaction_type_type']"
                :account="$account ?? null"
                :source="['id' => $transaction['source_account_id'], 'balance_after' => $transaction['source_balance_after'], 'type' => $transaction['source_account_type']]"
                :destination="['id' => $transaction['destination_account_id'], 'balance_after' => $transaction['destination_balance_after'], 'type' => $transaction['destination_account_type']]"
            />
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
        @if($showCategory ?? false)
            <td class="d-xs-none {{ $className }}">
                @if(null !== $transaction['category_id'])
                    <a href="{{ route('categories.show', [$transaction['category_id']]) }}" title="{{ $transaction['category_name'] }}">{{ $transaction['category_name'] }}</a>
                @endif
            </td>
        @endif
        @if($showBudget ?? false)
            <td class="d-xs-none {{ $className }}">
                @if(null !== $transaction['budget_id'])
                <a href="{{ route('budgets.show', [$transaction['budget_id']]) }}"
                   title="{{ $transaction['budget_name'] }}">{{ $transaction['budget_name'] }}</a>
                @endif
            </td>
        @endif

        @if(1 === $group['count'])
        <td class="d-xs-none {{ $className }} text-end">
                <button class="btn btn-sm btn-outline-secondary dropdown-toggle" type="button" id="journal_menu_{{ $transaction['transaction_journal_id'] }}" data-bs-toggle="dropdown" aria-expanded="false">{{ __('firefly.actions') }} <span class="caret"></span></button>
                <ul class="dropdown-menu" aria-labelledby="journal_menu_{{ $transaction['transaction_journal_id'] }}">
                    <li><a class="dropdown-item" href="{{ route('transactions.edit', [$group['id']]) }}"><span class="bi bi-pencil"></span> {{ __('firefly.edit') }}</a></li>
                    @if($transaction['transaction_type_type'] !== 'Opening balance' && $transaction['transaction_type_type'] !== 'Liability credit')
                        <li><a class="dropdown-item" href="{{ route('transactions.delete', [$group['id']]) }}"><span class="text-danger bi bi-trash"></span> {{ __('firefly.delete') }}</a></li>
                    @endif
                    @if($transaction['transaction_type_type'] !== 'Reconciliation' and $transaction['transaction_type_type'] !== 'Opening balance' and $transaction['transaction_type_type'] !== 'Liability credit')
                        <li><a class="dropdown-item" href="#" data-id="{{ $group['id'] }}" class="clone-transaction"><span class="bi bi-copy"></span> {{ __('firefly.clone') }}</a></li>
                        <li><a class="dropdown-item" href="#" data-id="{{ $group['id'] }}" class="clone-transaction-and-edit"><span class="bi bi-copy"></span> {{ __('firefly.clone_and_edit') }}</a></li>
                        <li><a class="dropdown-item" href="{{ route('rules.create-from-journal', [$transaction['transaction_journal_id']]) }}"><span class="bi bi-shuffle"></span> {{ __('firefly.create_rule_from_transaction') }}</a></li>
                    @endif
                </ul>
        </td>

        @endif
        @if(1 !== $group['count'])
        <td class="d-xs-none {{ $className }}">
            &nbsp;
        </td>
        @endif
        <td class="d-xs-none {{ $className }}">
            @if($transaction['transaction_type_type'] !== 'Reconciliation' and $transaction['transaction_type_type'] !== 'Opening balance' and $transaction['transaction_type_type'] !== 'Liability credit')
            <div class="text-end">
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
        <td colspan="8">&nbsp;</td>
        <td class="d-xs-none text-end">
            <div class="d-none action-menu">
            <button class="btn btn-sm btn-outline-secondary dropdown-toggle" type="button" id="bottom_action_menu" data-bs-toggle="dropdown" aria-expanded="false">{{ __('firefly.actions') }} <span class="caret"></span></button>
            <ul class="dropdown-menu" aria-labelledby="bottom_action_menu">
                <li><a href="#" class="mass-edit dropdown-item"><span class="bi bi-pencil"></span><span>{{ __('firefly.mass_edit') }}</span></a></li>
                <li><a href="#" class="bulk-edit dropdown-item"><span class="bi bi-pencil-square"></span><span>{{ __('firefly.bulk_edit') }}</span></a></li>
                <li><a href="#" class="dropdown-item mass-delete"><span class="bi bi-trash"></span><span>{{ __('firefly.mass_delete') }}</span></a></li>
            </ul>
            </div>
        </td>

        <td>&nbsp;</td>

    </tr>
    <tr>
        @if($showCategory || $showBudget ?? false)
        <td colspan="9" class="no-margin-pagination">{{ $groups->links('pagination.bootstrap-4') }}</td>
        @else
        <td colspan="8" class="no-margin-pagination">{{ $groups->links('pagination.bootstrap-4') }}</td>
        @endif
    </tr>
    </tfoot>
</table>
