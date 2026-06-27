



@if(\FireflyIII\Enums\TransactionTypeEnum::DEPOSIT->value === $transaction['transaction_type_type'])
    {!! format_amount_by_symbol(bcmul($transaction['amount'],'-1'), $transaction['currency_symbol'], $transaction['currency_decimal_places']) !!}
    @if(null !== $transaction['foreign_amount'])
        ({!! format_amount_by_symbol(bcmul($transaction['foreign_amount'], '-1'), $transaction['foreign_currency_symbol'], $transaction['foreign_currency_decimal_places']) !!})
    @endif
    @if($convertToPrimary && null !== $transaction['pc_amount'])
        ({!! format_amount_by_symbol(bcmul($transaction['pc_amount'], '-1'), $primaryCurrency->symbol, $primaryCurrency->decimal_places) !!})
    @endif
@endif

@if(\FireflyIII\Enums\TransactionTypeEnum::WITHDRAWAL->value === $transaction['transaction_type_type'])
    {!! format_amount_by_symbol($transaction['amount'], $transaction['currency_symbol'], $transaction['currency_decimal_places']) !!}
    @if(null !== $transaction['foreign_amount'])
        ({!! format_amount_by_symbol($transaction['foreign_amount'], $transaction['foreign_currency_symbol'], $transaction['foreign_currency_decimal_places']) !!})
    @endif
    @if($convertToPrimary && null !== $transaction['pc_amount'])
        ({!! format_amount_by_symbol($transaction['pc_amount'], $primaryCurrency->symbol, $primaryCurrency->decimal_places) !!})
    @endif
@endif

@if(\FireflyIII\Enums\TransactionTypeEnum::TRANSFER->value === $transaction['transaction_type_type'])
    <span class="text-info money-transfer">
    {{-- transfer away --}}
    @if(isset($account) && $transaction['source_account_id'] === $account->id)
        {!! format_amount_by_symbol(bcmul($transaction['amount'],'-1'), $transaction['currency_symbol'], $transaction['currency_decimal_places'], false) !!}
        @if(null !== $transaction['foreign_amount'])
            ({!! format_amount_by_symbol(bcmul($transaction['foreign_amount'],'-1'), $transaction['foreign_currency_symbol'], $transaction['foreign_currency_decimal_places'], false) !!})
        @endif
        @if($convertToPrimary && null !== $transaction['pc_amount'])
            ({!! format_amount_by_symbol(bcmul($transaction['pc_amount'], '-1'), $primaryCurrency->symbol, $primaryCurrency->decimal_places, false) !!})
        @endif
    @endif

    {{-- transfer in (default) --}}
    @if(!isset($account) || $transaction['source_account_id'] !== $account->id)
        {!! format_amount_by_symbol($transaction['amount'], $transaction['currency_symbol'], $transaction['currency_decimal_places'], false) !!}
        @if(null !== $transaction['foreign_amount'])
            ({!! format_amount_by_symbol($transaction['foreign_amount'], $transaction['foreign_currency_symbol'], $transaction['foreign_currency_decimal_places'], false) !!})
        @endif
        @if($convertToPrimary && null !== $transaction['pc_amount'])
            ({!! format_amount_by_symbol($transaction['pc_amount'], $primaryCurrency->symbol, $primaryCurrency->decimal_places, false) !!})
        @endif
    @endif
    </span>
@endif


@if(\FireflyIII\Enums\TransactionTypeEnum::OPENING_BALANCE->value === $transaction['transaction_type_type'])
    {{-- Opening balance is deposited on this account (render as positive amount) --}}
    @if(\FireflyIII\Enums\AccountTypeEnum::INITIAL_BALANCE->value === $transaction['source_account_type'])
        {!! format_amount_by_symbol(bcmul($transaction['amount'],'-1'), $transaction['currency_symbol'], $transaction['currency_decimal_places'], false) !!}
        @if(null !== $transaction['foreign_amount'])
            ({!! format_amount_by_symbol(bcmul($transaction['foreign_amount'],'-1'), $transaction['foreign_currency_symbol'], $transaction['foreign_currency_decimal_places'], false) !!})
        @endif
        @if($convertToPrimary && null !== $transaction['pc_amount'])
            ({!! format_amount_by_symbol(bcmul($transaction['pc_amount'], '-1'), $primaryCurrency->symbol, $primaryCurrency->decimal_places, false) !!})
        @endif
    @endif

    {{-- Opening balance is removed from this account (render as negative amount) --}}
    @if(\FireflyIII\Enums\AccountTypeEnum::INITIAL_BALANCE->value === $transaction['destination_account_type'])
        {!! format_amount_by_symbol($transaction['amount'], $transaction['currency_symbol'], $transaction['currency_decimal_places'], false) !!}
        @if(null !== $transaction['foreign_amount'])
            ({!! format_amount_by_symbol($transaction['foreign_amount'], $transaction['foreign_currency_symbol'], $transaction['foreign_currency_decimal_places'], false) !!})
        @endif
        @if($convertToPrimary && null !== $transaction['pc_amount'])
            ({!! format_amount_by_symbol($transaction['pc_amount'], $primaryCurrency->symbol, $primaryCurrency->decimal_places, false) !!})
        @endif
    @endif
@endif

@if(\FireflyIII\Enums\TransactionTypeEnum::RECONCILIATION->value === $transaction['transaction_type_type'])
    {{-- Reconciliation correction is deposited on this account (render as positive amount) --}}
    @if(\FireflyIII\Enums\AccountTypeEnum::RECONCILIATION->value === $transaction['source_account_type'])
        {!! format_amount_by_symbol(bcmul($transaction['amount'],'-1'), $transaction['currency_symbol'], $transaction['currency_decimal_places'], false) !!}
        @if(null !== $transaction['foreign_amount'])
            ({!! format_amount_by_symbol(bcmul($transaction['foreign_amount'],'-1'), $transaction['foreign_currency_symbol'], $transaction['foreign_currency_decimal_places'], false) !!})
        @endif
        @if($convertToPrimary && null !== $transaction['pc_amount'])
            ({!! format_amount_by_symbol(bcmul($transaction['pc_amount'], '-1'), $primaryCurrency->symbol, $primaryCurrency->decimal_places, false) !!})
        @endif
    @endif

    {{-- Reconciliation correction is removed from this account (render as negative amount) --}}
    @if(\FireflyIII\Enums\AccountTypeEnum::RECONCILIATION->value === $transaction['destination_account_type'])
        {!! format_amount_by_symbol($transaction['amount'], $transaction['currency_symbol'], $transaction['currency_decimal_places'], false) !!}
        @if(null !== $transaction['foreign_amount'])
            ({!! format_amount_by_symbol($transaction['foreign_amount'], $transaction['foreign_currency_symbol'], $transaction['foreign_currency_decimal_places'], false) !!})
        @endif
        @if($convertToPrimary && null !== $transaction['pc_amount'])
            ({!! format_amount_by_symbol($transaction['pc_amount'], $primaryCurrency->symbol, $primaryCurrency->decimal_places, false) !!})
        @endif
    @endif
@endif


{{--
<span class="text-end small">


{% elseif transaction.transaction_type_type == 'Reconciliation' %}
@else
  {!! format_amount_by_symbol(transaction.amount, transaction.currency_symbol, transaction.currency_decimal_places) }}
  {% if null != transaction.foreign_amount %}
      ({!! format_amount_by_symbol(transaction.foreign_amount, transaction.foreign_currency_symbol, transaction.foreign_currency_decimal_places) }})
  @endif
  {% if convertToPrimary and null != transaction.pc_amount %}
      ({!! format_amount_by_symbol(transaction.pc_amount, $primaryCurrency->symbol, foreign_currency_.decimal_places) }})
  @endif
@endif
    </span>
    --}}
