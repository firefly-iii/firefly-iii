@extends('layout.v3.session')
@section('content')
    <div class="row">
        <div class="col-lg-6">
            <div class="card mb-2">
                <div class="card-header">
                    <div class="row">
                        <div class="col">
                            <h3 class="card-title">{{ __('firefly.transaction_journal_information') }}</h3>
                        </div>
                        <div class="col text-end">
                            <button class="btn btn-sm btn-secondary-outline dropdown-toggle" type="button" id="card_header_menu" data-bs-toggle="dropdown" aria-expanded="false">{{ __('firefly.actions') }} <span class="caret"></span></button>
                            <ul class="dropdown-menu" aria-labelledby="card_header_menu">
                                    {{-- edit + delete --}}
                                    <li><a class="dropdown-item" href="{{ route('transactions.edit', [$transactionGroup->id]) }}"><span
                                                class="bi bi-pencil"></span> {{ __('firefly.edit') }}</a></li>
                                    @if($groupArray['transactions'][0]['type'] !== 'reconciliation' && $groupArray['transactions'][0]['type'] !== 'opening balance' && $groupArray['transactions'][0]['type'] !== 'liability credit')
                                        <li><a class="dropdown-item" href="{{ route('transactions.delete', [$transactionGroup->id]) }}"><span
                                                    class="bi bi-trash"></span> {{ __('firefly.delete') }}</a></li>
                                    @endif
                                    @if($groupArray['transactions'][0]['type'] !== 'reconciliation' && $groupArray['transactions'][0]['type'] !== 'opening balance' && $groupArray['transactions'][0]['type'] !== 'liability credit')
                                        <li role="separator" class="divider"></li>

                                        {{-- convert to different type --}}
                                        @if($groupArray['transactions'][0]['type'] !== 'withdrawal')
                                            <li>
                                                <a class="dropdown-item" href="{{ route('transactions.convert.index', ['withdrawal', $transactionGroup->id]) }}"><span
                                                        class="bi bi-arrow-left-right"></span> {{ __('firefly.convert_to_withdrawal') }}</a></li>
                                        @endif

                                        @if($groupArray['transactions'][0]['type'] !== 'deposit')
                                            <li>
                                                <a class="dropdown-item" href="{{ route('transactions.convert.index', ['deposit', $transactionGroup->id]) }}"><span
                                                        class="bi bi-arrow-left-right"></span> {{ __('firefly.convert_to_deposit') }}</a></li>
                                        @endif

                                        @if($groupArray['transactions'][0]['type'] !== 'transfer')
                                            <li>
                                                <a class="dropdown-item" href="{{ route('transactions.convert.index', ['transfer', $transactionGroup->id]) }}"><span
                                                        class="bi bi-arrow-left-right"></span> {{ __('firefly.convert_to_transfer') }}</a></li>
                                        @endif


                                        {{--  clone --}}
                                        @if($groupArray['transactions'][0]['type'] !== 'opening balance' && $groupArray['transactions'][0]['type'] !== 'reconciliation')
                                            <li role="separator" class="divider"></li>
                                            <li><a class="dropdown-item" href="#" class="clone-transaction" data-id="{{ $transactionGroup->id }}"><span
                                                        class="bi bi-copy"></span> {{ __('firefly.clone') }}</a></li>
                                            <li><a class="dropdown-item" href="#" class="clone-transaction-and-edit" data-id="{{ $transactionGroup->id }}"><span
                                                        class="bi bi-copy"></span> {{ __('firefly.clone_and_edit') }}</a></li>
                                        @endif
                                    @endif

                                </ul>
                        </div>
                    </div>
                </div>
                <div class="card-body p-0">
                    <table class="table table-hover">
                        <tbody>
                        <tr>
                            <td class="forty">{{ trans('list.id') }}</td>
                            <td>#{{ $transactionGroup->id }}</td>
                        </tr>
                        <tr>
                            <td class="forty">{{ trans('list.type') }}</td>
                            <td>{{ __($first['transaction_type_type']) }}</td>
                        </tr>
                        <tr>
                            <td>{{ trans('list.description') }}</td>
                            <td>
                                @if(1 === $splits)
                                    {{ $first['description'] }}
                                @else
                                    {{ $transactionGroup->title }}
                                @endif
                            </td>
                        </tr>
                        <tr>
                            <td class="thirty">{{ trans('list.date') }}</td>
                            <td>
                                <span class="date-time" data-date="{{ $first['date']->toIso8601ZuluString() }}" title="{{ $first['date']->isoFormat($dateTimeFormat) }}@if('' !== $first['date_tz']) ({{ trans('firefly.stored_in_tz', ['timezone' => $first['date_tz']]) }}, {{ trans('firefly.displayed_in_tz', ['timezone' => config('app.timezone')]) }})@endif">
                                {{ $first['date']->isoFormat($dateTimeFormat) }}
                                @if('' !== $first['date_tz'])
                                    ({{ trans('firefly.stored_in_tz', ['timezone' => $first['date_tz']]) }})
                                @endif
                                </span>
                            </td>
                        </tr>
                        </tbody>
                    </table>
                </div>
            </div>
            @if(count($groupLogEntries) > 0)
                <div class="card mb-2">
                    <div class="card-header">
                        <h3 class="card-title">
                            {{ __('firefly.audit_log_entries') }}
                        </h3>
                    </div>
                    <div class="card-body p-0">
                        {% include 'list.ale' with {logEntries: groupLogEntries} %}
                    </div>
                </div>
            @endif

        </div>
        <div class="col-lg-6">
            <div class="card mb-2">
                <div class="card-header">
                    <h3 class="card-title">{{ __('firefly.transaction_journal_meta') }}</h3>
                </div>
                <div class="card-body p-0">
                    <table class="table table-hover">
                        <tbody>
                        @if($first['transaction_type_type'] !== 'Withdrawal' || $splits === 1)
                            <tr>
                                <td class="forty">
                                    {{ trans_choice('firefly.source_accounts', count($accounts['source'])) }}
                                </td>
                                <td>
                                    @foreach($accounts['source'] as $account)
                                        @if('Cash account' === $account['type'])
                                            <span class="text-success">({{ __('firefly.cash') }})</span>
                                        @else
                                            <a href="{{ route('accounts.show',$account['id']) }}"
                                               title="{{ $account['iban'] ?? $account['name'] }}">
                                                {{ $account['name'] }}
                                            </a>&ZeroWidthSpace;@endif&ZeroWidthSpace;@if($loop->index !==count($accounts['source'])-1),@endif
                                    @endforeach
                                </td>
                            </tr>
                        @endif
                        @if('Deposit' === $first['transaction_type_type'] || 1 === $splits)
                            <tr>
                                <td>
                                    {{ trans_choice('firefly.destination_accounts', count($accounts['destination'] )) }}

                                </td>
                                <td>
                                    @foreach($accounts['destination'] as $account)
                                        @if('Cash account' === $account['type'])
                                            <span class="text-success">({{ __('firefly.cash') }})</span>
                                        @else
                                            <a href="{{ route('accounts.show',$account['id']) }}" title="{{ $account['iban'] ?? $account['name'] }}">{{ $account['name'] }}</a>&ZeroWidthSpace;@endif&ZeroWidthSpace;@if($loop->index !==count($accounts['source'])-1),@endif
                                    @endforeach
                                </td>
                            </tr>
                        @endif
                        <tr>
                            <td class="thirty">{{ __('firefly.total_amount') }}</td>
                            <td>
                                @foreach($amounts as $amount)
                                    @if($first['transaction_type_type'] === 'Withdrawal')
                                        @if($amount['approximate'])~ @endif{!! format_amount_by_symbol($amount['amount'],$amount['symbol'], $amount['decimal_places']) !!}@if($loop->index !== count($amounts)-1), @endif
                                    @elseif($first['transaction_type_type'] === 'Deposit')
                                        @if($amount['approximate'])~ @endif{!! format_amount_by_symbol($amount['amount']*-1,$amount['symbol'], $amount['decimal_places']) !!}@if($loop->index !== count($amounts)-1), @endif
                                    @elseif($first['transaction_type_type'] === 'Transfer')
                                        <span class="text-info money-transfer">
                                        @if($amount['approximate'])~ @endif{!! format_amount_by_symbol($amount['amount']*-1, $amount['symbol'], $amount['decimal_places'], false) !!}@if($loop->index !== count($amounts)-1), @endif
                                    </span>
                                    @elseif($first['transaction_type_type'] === 'Opening balance')
                                        {{-- Opening balance stored amount is always negative: find out which way the money goes --}}
                                        @if($groupArray['transactions'][0]['source_account_type'] === 'Initial balance account')
                                            {!! format_amount_by_symbol($amount['amount']*-1,$amount['symbol'], $amount['decimal_places']) !!}
                                        @else
                                            {!! format_amount_by_symbol($amount['amount'],$amount['symbol'], $amount['decimal_places']) !!}
                                        @endif
                                    @endif
                                @endforeach
                            </td>
                        </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
    <div class="row">

    </div>
    @if($splits > 1)
        <div class="row">
            <div class="col-lg-12">
                <h3>{{ __('firefly.splits') }}</h3>
            </div>
        </div>
    @endif
    @php
    $boxSize = 6;
    @endphp
    @if($splits > 2)
        @php
            $boxSize = 4;
        @endphp
    @endif
    <div class="row">
        @foreach($selectedGroup['transactions'] as $index => $journal)
            <div class="col-lg-{{ $boxSize }}">
                <div class="card mb-2">
                    <div class="card-header">
                        <div class="row">
                            <div class="col">
                                <h3 class="card-title">
                                    {{ $journal['description'] }}
                                    @if($journal['reconciled'])
                                        <span class="bi bi-check"></span>
                                    @endif
                                    @if($splits > 1)
                                        <small>
                                            {{ $loop->index+1 }} / {{ $splits }}
                                        </small>
                                    @endif
                                </h3>
                            </div>
                            <div class="col text-end">
                                <button class="btn btn-sm btn-secondary-outline dropdown-toggle" type="button" id="menu_journal_{{ $journal['transaction_journal_id'] }}" data-bs-toggle="dropdown" aria-expanded="false">{{ __('firefly.actions') }} <span class="caret"></span></button>
                                <ul class="dropdown-menu" aria-labelledby="menu_journal_{{ $journal['transaction_journal_id'] }}">
                                        {{-- edit + delete --}}
                                        <li><a class="dropdown-item" href="{{ route('transactions.edit', [$transactionGroup->id]) }}"><span
                                                    class="bi bi-pencil"></span> {{ __('firefly.edit') }}</a></li>
                                        @if($groupArray['transactions'][0]['type'] !== 'reconciliation' && $groupArray['transactions'][0]['type'] !== 'opening balance' && $groupArray['transactions'][0]['type'] !== 'liability credit')
                                            <li><a class="dropdown-item" href="{{ route('transactions.delete', [$transactionGroup->id]) }}"><span
                                                        class="bi bi-trash"></span> {{ __('firefly.delete') }}</a></li>
                                        @endif
                                        @if($journal['reconciled'])
                                            <li><a class="dropdown-item" class="reconcile-button" href="{{ route('transactions.unreconcile', [$journal['transaction_journal_id']]) }}"><span
                                                        class="fa fa-history"></span> {{ __('firefly.unreconcile') }}</a></li>
                                        @endif
                                        @if($groupArray['transactions'][0]['type'] !== 'reconciliation' && $groupArray['transactions'][0]['type'] !== 'opening balance' && $groupArray['transactions'][0]['type'] !== 'liability credit')
                                            <li role="separator" class="divider"></li>

                                            {{-- convert to different type --}}
                                            @if($groupArray['transactions'][0]['type'] !== 'withdrawal')
                                                <li>
                                                    <a class="dropdown-item" href="{{ route('transactions.convert.index', ['withdrawal', $transactionGroup->id]) }}"><span
                                                            class="bi bi-arrow-left-right"></span> {{ __('firefly.convert_to_withdrawal') }}</a>
                                                </li>
                                            @endif

                                            @if($groupArray['transactions'][0]['type'] !== 'deposit')
                                                <li>
                                                    <a class="dropdown-item" href="{{ route('transactions.convert.index', ['deposit', $transactionGroup->id]) }}"><span
                                                            class="bi bi-arrow-left-right"></span> {{ __('firefly.convert_to_deposit') }}</a></li>
                                            @endif

                                            @if($groupArray['transactions'][0]['type'] !== 'transfer')
                                                <li>
                                                    <a class="dropdown-item" href="{{ route('transactions.convert.index', ['transfer', $transactionGroup->id]) }}"><span
                                                            class="bi bi-arrow-left-right"></span> {{ __('firefly.convert_to_transfer') }}</a>
                                                </li>
                                            @endif

                                            {{--  clone --}}
                                            @if($groupArray['transactions'][0]['type'] !== 'opening balance' && $groupArray['transactions'][0]['type'] !== 'reconciliation')
                                                <li role="separator" class="divider"></li>
                                                <li><a class="dropdown-item" href="#" data-id="{{ $transactionGroup->id }}" class="clone-transaction"><span
                                                            class="bi bi-copy"></span> {{ __('firefly.clone') }}</a></li>
                                                <li><a class="dropdown-item" href="#" data-id="{{ $transactionGroup->id }}" class="clone-transaction-and-edit"><span
                                                            class="bi bi-copy"></span> {{ __('firefly.clone_and_edit') }}</a></li>
                                            @endif

                                            <li><a class="dropdown-item link-modal" href="#"
                                                   data-journal="{{ $journal['transaction_journal_id'] }}"><span
                                                        class="bi bi-link"></span>{{ __('firefly.link_transaction') }}</a></li>
                                            <li role="separator" class="divider"></li>
                                            <li>
                                                <a class="dropdown-item" href="{{ route('rules.create-from-journal', [$journal['transaction_journal_id']]) }}"><span
                                                        class="fa fa-random"></span>{{ __('firefly.create_rule_from_transaction') }}
                                                </a></li>
                                            <li>
                                                <a class="dropdown-item" href="{{ route('recurring.create-from-journal', [$journal['transaction_journal_id']]) }}"><span
                                                        class="fa fa-paint-brush"></span>{{ __('firefly.create_recurring_from_transaction') }}
                                                </a></li>
                                        @endif
                                    </ul>
                            </div>
                        </div>
                    </div>
                    <div class="card-body p-0">
                        <table class="table">
                            <tr>
                                <td colspan="2">
                                    @if('Cash account' === $journal['source_account_type'])
                                        <span class="text-success">({{ __('firefly.cash') }})</span>
                                    @else
                                        <a href="{{ route('accounts.show', $journal['source_account_id']) }}"
                                           title="{{ $journal['source_iban'] ?? $journal['source_account_name'] }}">{{ $journal['source_account_name'] }}</a> &rarr;
                                    @endif

                                    @if($first['transaction_type_type'] === 'Withdrawal')
                                        {!! format_amount_by_symbol($journal['amount'], $journal['currency_symbol'], $journal['currency_decimal_places'])  !!}
                                    @elseif($first['transaction_type_type'] === 'Deposit')
                                        {!! format_amount_by_symbol($journal['amount']*-1, $journal['currency_symbol'], $journal['currency_decimal_places'])  !!}
                                    @elseif($first['transaction_type_type'] === 'Transfer' or $first['transaction_type_type'] === 'Opening balance')
                                        <span class="text-info money-transfer">
                                        {!! format_amount_by_symbol($journal['amount']*-1, $journal['currency_symbol'], $journal['currency_decimal_places'], false)  !!}
                                    </span>
                                    @elseif($first['transaction_type_type'] === 'Liability credit')
                                        <span class="text-info money-transfer">
                                        {!! format_amount_by_symbol($journal['amount']*-1, $journal['currency_symbol'], $journal['currency_decimal_places'], false)  !!}
                                    </span>
                                    @endif

                                    <!-- do primary currency amount, if foreign amount is not the same. -->
                                    @if(null !== $journal['pc_amount'] && $primaryCurrency->id !== $journal['currency_id'] && $primaryCurrency->id !== $journal['foreign_currency_id'])
                                        @if($first['transaction_type_type'] === 'Withdrawal')
                                            (~ {!! format_amount_by_symbol($journal['pc_amount'], $primaryCurrency->symbol, $primaryCurrency->decimal_places) !!})
                                        @elseif($first['transaction_type_type'] === 'Deposit')
                                            (~ {!! format_amount_by_symbol($journal['pc_amount']*-1, $primaryCurrency->symbol, $primaryCurrency->decimal_places) !!})
                                        @elseif($first['transaction_type_type'] === 'Transfer')
                                            <span class="text-info money-transfer">
                                        (~ {!! format_amount_by_symbol($journal['pc_amount']*-1, $primaryCurrency->symbol, $primaryCurrency->decimal_places, false) !!})
                                    </span>
                                        @endif
                                    @endif

                                    <!-- do foreign amount -->
                                    @if(null !== $journal['foreign_amount'])
                                        @if($first['transaction_type_type'] === 'Withdrawal')
                                            ({!! format_amount_by_symbol($journal['foreign_amount'], $journal['foreign_currency_symbol'], $journal['foreign_currency_decimal_places']) !!})
                                        @elseif($first['transaction_type_type'] === 'Deposit')
                                            ({!! format_amount_by_symbol($journal['foreign_amount']*-1, $journal['foreign_currency_symbol'], $journal['foreign_currency_decimal_places']) !!})
                                        @elseif($first['transaction_type_type'] === 'Transfer')
                                            <span class="text-info money-transfer">
                                        ({!! format_amount_by_symbol($journal['foreign_amount']*-1, $journal['foreign_currency_symbol'], $journal['foreign_currency_decimal_places'], false) !!})
                                    </span>
                                        @endif
                                    @endif

                                    <!-- do foreign PC amount -->
                                    @if(null !== $journal['pc_foreign_amount'])
                                        @if($first['transaction_type_type'] === 'Withdrawal')
                                            ({!! format_amount_by_symbol($journal['pc_foreign_amount'], $primaryCurrency->symbol, $primaryCurrency->decimal_places) !!})
                                        @elseif($first['transaction_type_type'] === 'Deposit')
                                            ({!! format_amount_by_symbol($journal['pc_foreign_amount']*-1, $primaryCurrency->symbol, $primaryCurrency->decimal_places) !!})
                                        @elseif($first['transaction_type_type'] === 'Transfer')
                                            <span class="text-info money-transfer">
                                        ({!! format_amount_by_symbol($journal['pc_foreign_amount']*-1, $primaryCurrency->symbol, $primaryCurrency->decimal_places, false) !!})
                                    </span>
                                        @endif
                                    @endif


                                    &rarr;
                                    @if('Cash account' === $journal['destination_account_type'])
                                        <span class="text-success">({{ __('firefly.cash') }})</span>
                                    @else
                                        <a href="{{ route('accounts.show', $journal['destination_account_id']) }}"
                                           title="{{ $journal['destination_iban'] ?? $journal['destination_account_name'] }}">{{ $journal['destination_account_name'] }}</a>
                                    @endif
                                </td>
                            </tr>
                            <tr>
                                <td>{{ __('firefly.transaction_journal_id') }}</td>
                                <td>#{{ $journal['transaction_journal_id'] }}</td>
                            </tr>
                            @if(null !== $journal['category_id'])
                                <tr>
                                    <td class="thirty">{{ __('firefly.category') }}</td>
                                    <td>
                                        <a href="{{ route('categories.show', [$journal['category_id']]) }}">{{ $journal['category_name'] }}</a>
                                    </td>
                                </tr>
                            @endif
                            @if(null !== $journal['budget_id'] && $first['transaction_type_type'] === 'Withdrawal')
                                <tr>
                                    <td class="forty">{{ __('firefly.budget') }}</td>
                                    <td>
                                        <a href="{{ route('budgets.show', [$journal['budget_id']]) }}">{{ $journal['budget_name'] }}</a>
                                    </td>
                                </tr>
                            @endif
                            @if(null !== $journal['bill_id'] && $first['transaction_type_type'] === 'Withdrawal')
                                <tr>
                                    <td class="forty">{{ __('firefly.bill') }}</td>
                                    <td>
                                        <a href="{{ route('subscriptions.show', [$journal['bill_id']]) }}">{{ $journal['bill_name'] }}</a>
                                    </td>
                                </tr>
                            @endif
                            <!-- other fields -->
                            @foreach(['interest_date','book_date','process_date','due_date','payment_date','invoice_date'] as $dateField)
                                @if(journal_has_meta($journal['transaction_journal_id'], $dateField))
                                    <tr>
                                        <td class="forty">{{ trans('list.' . $dateField) }}</td>
                                        <td>{{ journal_get_meta_date($journal['transaction_journal_id'], $dateField)->isoFormat($monthAndDayFormat) }}</td>
                                    </tr>
                                @endif
                            @endforeach
                            @foreach(['external_id','bunq_payment_id','internal_reference','sepa_batch_id','sepa_ct_id','sepa_ct_op','sepa_db','sepa_country','sepa_cc','sepa_ep','sepa_ci','external_url'] as $metaField)
                                @if(journal_has_meta($journal['transaction_journal_id'], $metaField))
                                    <tr>
                                        <td>{{ trans('list.' . $metaField) }}</td>
                                        <td class="forty">
                                            @if('external_url' === $metaField)
                                                <a href="{{ journal_get_meta_field($journal['transaction_journal_id'], $metaField) }}" rel="noopener noreferrer nofollow" target="_blank">
                                                    @if(strlen(journal_get_meta_field($journal['transaction_journal_id'], $metaField)) > 60)
                                                        {{ substr(journal_get_meta_field($journal['transaction_journal_id'], $metaField),0,60) }}...
                                                    @else
                                                        {{ journal_get_meta_field($journal['transaction_journal_id'], $metaField) }}
                                                    @endif
                                                </a>
                                            @endif
                                            @if('external_url' !== $metaField)
                                                {{ journal_get_meta_field($journal['transaction_journal_id'], $metaField) }}
                                            @endif
                                        </td>
                                    </tr>
                                @endif
                            @endforeach
                            @if('' !== (string)$journal['notes'])
                                <tr>
                                    <td class="forty">{{ trans('list.notes') }}</td>
                                    <td class="markdown">{!! parse_markdown($journal['notes'] ?? '')  !!}</td>
                                </tr>
                            @endif
                            @if(journal_has_meta($journal['transaction_journal_id'], 'recurring_total') && journal_has_meta($journal['transaction_journal_id'], 'recurring_count'))
                                @php
                                    $recurringTotal = journal_get_meta_field($journal['transaction_journal_id'], 'recurring_total')
                                @endphp
                                @if(0 === $recurringTotal)
                                    @php
                                        $recurringTotal = '∞'
                                    @endphp
                                @endif
                                <tr>
                                    <td class="forty">{{ trans('list.recurring_transaction') }}</td>
                                    <td>{{ trans('firefly.recurring_info', ['total' => $recurringTotal, 'count' => journal_get_meta_field($journal['transaction_journal_id'], 'recurring_count')]) }}</td>
                                </tr>
                            @endif
                            @if(count($journal['tags']) > 0)
                                <tr>
                                    <td class="forty">{{ __('firefly.tags') }}</td>
                                    <td>
                                        @foreach($journal['tags'] as $tag)
                                            @if(null !== $tag['id'] && '' !== $tag['id'])
                                                <h4 class="inline"><a class="badge text-bg-success" href="{{ route('tags.show', [$tag['id']]) }}"><span class="bi bi-tag"></span>{{ $tag['tag'] }}</a></h4>
                                            @endif
                                        @endforeach
                                    </td>
                                </tr>
                            @endif
                        </table>
                    </div>
                </div>

                <!-- Transaction links -->
                @if(array_key_exists($journal['transaction_journal_id'], $links) && count($links[$journal['transaction_journal_id']]) > 0)
                    <div class="card mb-2">
                        <div class="card-header">
                            <h3 class="card-title">
                                {{ __('firefly.journal_links') }}
                            </h3>
                        </div>
                        <div class="card-body p-0">
                            <table class="table">
                                @foreach($links[$journal['transaction_journal_id']] as $link)
                                    <tr>
                                        <td class="onetwenty">
                                            <div class="btn-group btn-group-sm">
                                                <a href="#" class="btn btn-outline-secondary switch-link" data-id="{{ $link['id'] }}"><span
                                                        class="bi bi-arrow-left-right"></span></a>
                                                <a href="{{ route('transactions.link.delete', [$link['id']]) }}"
                                                   class="btn btn-danger"><span class="bi bi-trash"></span></a>
                                            </div>
                                        </td>
                                        <td>
                                            @if($link['editable'])
                                                {{ $link['link'] }}
                                            @else
                                                {{ trans('firefly.' . $link['link']) }}
                                            @endif
                                            "<a href="{{ route('transactions.show', $link['group']) }}"
                                                title="{{ $link['description'] }}">{{ $link['description'] }}</a>"

                                            ({!! $link['amount'] !!})
                                            @if('' !== $link['foreign_amount'])
                                                ({!! $link['foreign_amount'] !!})
                                            @endif
                                            @if($link['notes'] !== "")
                                                ({{ $link['notes'] }})
                                            @endif
                                        </td>
                                    </tr>
                                @endforeach
                            </table>
                        </div>
                    </div>
                @endif

                <!-- Attachments -->
                @if(array_key_exists($journal['transaction_journal_id'], $attachments) && count($attachments[$journal['transaction_journal_id']]) > 0)
                    <div class="card mb-2">
                        <div class="card-header">
                            <h3 class="card-title">{{ __('firefly.attachments') }}</h3>
                        </div>
                        <div class="card-body p-0">
                            {% include 'list.attachments' with {attachments: attachments[$journal['transaction_journal_id']]} %}
                        </div>
                    </div>
                @endif

                <!-- Piggy bank events -->
                @if(array_key_exists($journal['transaction_journal_id'], $events) && count($events[$journal['transaction_journal_id']]) > 0)
                    <div class="card mb-2">
                        <div class="card-header">
                            <h3 class="card-title">{{ __('firefly.piggy_events') }}</h3>
                        </div>
                        <div class="card-body p-0">
                            <table class="table table-hover">
                                @foreach($events[$journal['transaction_journal_id']] as $event)
                                    <tr>
                                        <td class="thirty">{{ $event['amount'] }}</td>
                                        <td>
                                            <a href="{{ route('piggy-banks.show', [$event['piggy_id']]) }}">{{ $event['piggy'] }}</a>
                                        </td>
                                    </tr>
                                @endforeach
                            </table>
                        </div>
                    </div>
                @endif
                @if(count($logEntries[$journal['transaction_journal_id']]) > 0)
                    <div class="card mb-2">
                        <div class="card-header">
                            <h3 class="card-title">
                                {{ __('firefly.audit_log_entries') }}
                            </h3>
                        </div>
                        <div class="card-body p-0">
                            {% include 'list.ale' with {logEntries: logEntries[$journal['transaction_journal_id']]} %}
                        </div>
                    </div>
                @endif
            </div>
        @endforeach
    </div>
    {{-- modal for linking journals. Will be filled by AJAX --}}
    <div class="modal fade" tabindex="-1" role="dialog" id="linkJournalModal">
    </div>
@endsection
@section('scripts')
    @vite(['js/pages/generic.js'])
    <script type="text/javascript" nonce="{{ $JS_NONCE }}">
        var modalDialogURL = '{{ route('transactions.link.modal', ['%JOURNAL%']) }}';
        var acURL = '{{ route('api.v1.autocomplete.transactions-with-id') }}';
        var groupURL = '{{ route('transactions.show',['%GROUP%']) }}';
        var cloneGroupUrl = '{{ route('transactions.clone') }}';
        var cloneAndEditUrl = '{{ route('transactions.clone') }}?redirect=edit';


        $('.switch-link').on('click', switchLink);
        $('.reconcile-button').on('click', unreconcile);
        var switchLinkUrl = '{{ route('transactions.link.switch') }}';

        function unreconcile(e) {
            e.preventDefault();
            var obj = $(e.currentTarget);
            $.post(obj.attr('href'), {
                _token: token
            }).done(function () {
                location.reload();
            }).fail(function () {
                console.error('I failed :(');
            });

            return false
        }

        function switchLink(e) {
            e.preventDefault();
            var obj = $(e.currentTarget);
            $.post(switchLinkUrl, {
                _token: token,
                id: obj.data('id')
            }).done(function () {
                location.reload();
            }).fail(function () {
                console.error('I failed :(');
            });

            //alert(obj.data('id'));

            return false
        }
    </script>
    <script type="text/javascript" src="v1/js/lib/typeahead/typeahead.bundle.min.js?v={{ $FF_BUILD_TIME }}"
            nonce="{{ $JS_NONCE }}"></script>
    <script type="text/javascript" src="v1/js/ff/transactions/show.js?v={{ $FF_BUILD_TIME }}"
            nonce="{{ $JS_NONCE }}"></script>
@endsection

