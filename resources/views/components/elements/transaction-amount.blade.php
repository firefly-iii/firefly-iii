<!-- TODO amount display helper -->
{{-- deposit --}}
@if('Deposit' === $type)
    {{-- amount of deposit --}}
    {!! formatAmountBySymbol($amount['amount']*-1, $amount['currency_symbol'], $amount['currency_decimal_places']) !!}
    {{-- foreign amount of deposit --}}
    @if(null !== $foreign['amount'])
        ({!! formatAmountBySymbol($foreign['amount']*-1, $foreign['currency_symbol'], $foreign['currency_decimal_places']) !!})
    @endif
    {{--  primary currency amount of deposit --}}
    @if($convertToPrimary && 0 != $pcAmount)
        (~ {!! formatAmountBySymbol($pcAmount*-1, $primaryCurrency->symbol, $primaryCurrency->decimal_places) !!})
    @endif
    {{-- transfer --}}
@elseif('Transfer' === $type)
    {{-- amount of transfer --}}
    <span class="text-info money-transfer">
                    {{-- present as negative. --}}
        @if($transaction['source_account_id'] === $account?->id)
            neg {!! formatAmountBySymbol($amount['amount'], $amount['currency_symbol'], $amount['currency_decimal_places'], false) !!}
        @endif
        {{-- present as positive --}}
        @if($transaction['source_account_id'] !== $account?->id)
            {!! formatAmountBySymbol($amount['amount']*-1, $amount['currency_symbol'], $amount['currency_decimal_places'], false) !!}
        @endif
        {{-- foreign amount of transfer (negative) --}}
        @if(null !== $foreign['amount'] && $transaction['source_account_id'] === $account?->id)
            neg ({!! formatAmountBySymbol($foreign['amount'], $foreign['currency_symbol'], $foreign['currency_decimal_places'], false) !!})
        @endif
        {{-- foreign amount of transfer (positive) --}}
        @if(null !== $foreign['amount'] && $transaction['source_account_id'] !== $account?->id)
            ({!! formatAmountBySymbol($foreign['amount']*-1, $foreign['currency_symbol'], $foreign['currency_decimal_places'], false) !!})
        @endif
        {{-- transfer in primary currency. Does not care about direction. --}}
        @if($convertToPrimary && 0 !== $pcAmount)
            (~ {!! formatAmountBySymbol($pcAmount*-1, $primaryCurrency->symbol, $primaryCurrency->decimal_places) !!})
        @endif
                </span>
    {{-- opening balance --}}
@elseif('Opening balance' === $type)
    {{-- Is a positive opening balance, present as positive. --}}
    @if('Initial balance account' === $transaction['source_account_type'])
        {!! formatAmountBySymbol($amount['amount']*-1, $amount['currency_symbol'], $amount['currency_decimal_places']) !!}
        {{-- opening balance may have foreign amount (also pos) --}}
        @if(null !== $foreign['amount'])
            ({!! formatAmountBySymbol($foreign['amount']*-1, $foreign['currency_symbol'], $foreign['currency_decimal_places']) !!})
        @endif
        {{-- possibly, primary amount. --}}
        @if($convertToPrimary && 0 !== $pcAmount)
            (~ {!! formatAmountBySymbol($pcAmount*-1, $primaryCurrency->symbol, $primaryCurrency->decimal_places) !!})
        @endif
    @else
        {{-- withdrawal but also any other transaction type: --}}
        {!! formatAmountBySymbol($amount['amount'], $amount['currency_symbol'], $amount['currency_decimal_places']) !!}
        @if(null !== $foreign['amount'])
            ({!! formatAmountBySymbol($foreign['amount'], $foreign['currency_symbol'], $foreign['currency_decimal_places']) !!})
        @endif
        @if($convertToPrimary && 0 !== $pcAmount)
            (~ {!! formatAmountBySymbol($pcAmount, $primaryCurrency->symbol, $primaryCurrency->decimal_places) !!})
        @endif
    @endif
@elseif('Reconciliation' === $type)
    {{-- Reconciliation positive--}}
    @if('Reconciliation account' === $transaction['source_account_type'])
        {{-- amount, also foreign and converted. --}}
        {!! formatAmountBySymbol($amount['amount']*-1, $amount['currency_symbol'], $amount['currency_decimal_places']) !!}
        @if(null !== $foreign['amount'])
            ({!! formatAmountBySymbol($foreign['amount']*-1, $foreign['currency_symbol'], $foreign['currency_decimal_places']) !!})
        @endif
        @if($convertToPrimary && 0 !== $pcAmount)
            (~ {!! formatAmountBySymbol($pcAmount*-1, $primaryCurrency->symbol, $primaryCurrency->decimal_places) !!})
        @endif
    @else
        {{-- Reconciliation negative --}}
        {!! formatAmountBySymbol($amount['amount'], $amount['currency_symbol'], $amount['currency_decimal_places']) !!}
        @if(null !== $foreign['amount'])
            ({!! formatAmountBySymbol($foreign['amount'], $foreign['currency_symbol'], $foreign['currency_decimal_places']) !!})
        @endif
        @if($convertToPrimary && 0 !== $pcAmount)
            (~ {!! formatAmountBySymbol($pcAmount, $primaryCurrency->symbol, $primaryCurrency->decimal_places) !!})
        @endif
    @endif
@elseif('Liability credit' === $type)
    {{-- liability credit positive--}}
    @if('Liability credit' === $transaction['source_account_type'])
        {!! formatAmountBySymbol($amount['amount'], $amount['currency_symbol'], $amount['currency_decimal_places']) !!}
        @if(null !== $foreign['amount'])
            ({!! formatAmountBySymbol($foreign['amount'], $foreign['currency_symbol'], $foreign['currency_decimal_places']) !!})
        @endif
        @if($convertToPrimary && 0 !== $pcAmount)
            (~ {!! formatAmountBySymbol($pcAmount, $primaryCurrency->symbol, $primaryCurrency->decimal_places) !!})
        @endif
    @else
        {!! formatAmountBySymbol($amount['amount']*-1, $amount['currency_symbol'], $amount['currency_decimal_places']) !!}
        @if(null !== $foreign['amount'])
            ({!! formatAmountBySymbol($foreign['amount']*-1, $foreign['currency_symbol'], $foreign['currency_decimal_places']) !!})
        @endif
        @if($convertToPrimary && 0 !== $pcAmount)
            (~ {!! formatAmountBySymbol($pcAmount*-1, $primaryCurrency->symbol, $primaryCurrency->decimal_places) !!})
        @endif
    @endif
@else
    {{--  THE REST most likely, withdrawal but also any other transaction type: --}}
    {!! formatAmountBySymbol($amount['amount'], $amount['currency_symbol'], $amount['currency_decimal_places']) !!}
    {{-- foreign amount of withdrawal --}}
    @if(null !== $foreign['amount'])
        ({!! formatAmountBySymbol($foreign['amount'], $foreign['currency_symbol'], $foreign['currency_decimal_places']) !!})
    @endif
    {{--  primary currency amount of withdrawal, if not in foreign currency --}}
    @if($convertToPrimary && 0 !== $pcAmount && $primaryCurrency->id !== $foreign['currency_id'])
        (~ {!! formatAmountBySymbol($pcAmount, $primaryCurrency->symbol, $primaryCurrency->decimal_places) !!})
    @endif
@endif
