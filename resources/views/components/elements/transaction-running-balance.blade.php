{{-- RUNNING BALANCE --}}
@if(false === $balanceDirty && '' !== $destination['balance_after'] && '' !== $source['balance_after'])
    @if('Deposit' === $type)
        @if($source['id'] === $account?->id)
            <span title="Deposit, source">{!! formatAmountBySymbol($source['balance_after'], $currency['symbol'], $currency['decimal_places']) !!}</span>
        @else
            @if('Revenue account' === $source['type'])
                <span title="Deposit from revenue">{!! formatAmountBySymbol($destination['balance_after'], $currency['symbol'], $currency['decimal_places']) !!}</span>
            @else
                <span title="Deposit from liab">{!! formatAmountBySymbol($destination['balance_after'], $foreign['currency_symbol'], $foreign['decimal_places']) !!}</span>
            @endif
            {{-- if this is a deposit from revenue account, use the destination account currency? For #12043 and #12169. Otherwise, keep at source account -}}
            {{-- changed from normal currency_symbol to foreign_currency_symbol for #12043 --}}
        @endif
    @elseif('Withdrawal' === $type)
        {{-- withdrawal into a liability --}}
        @if(in_array($destination['type'], ['Mortgage','Debt','Loan'], true))
                @if($account?->id === $source['id'])
                    <span title="Withdrawal, liab, source">{!! formatAmountBySymbol($source['balance_after'], $currency['symbol'], $currency['decimal_places']) !!}</span>
                @elseif($account?->id === $destination['id'])
                    <span title="Withdrawal, liab, dest">{!! formatAmountBySymbol($destination['balance_after'], $currency['symbol'], $currency['decimal_places']) !!}</span>
                @else
                    -
                @endif
            {{-- withdrawal into an expense account --}}
        @else
            @if($account?->id === $source['id'])
                <span title="Withdrawal, source">{!! formatAmountBySymbol($source['balance_after'], $currency['symbol'], $currency['decimal_places']) !!}</span>
            @elseif($account?->id === $destination['id'])
                <span title="Withdrawal, dest">{!! formatAmountBySymbol($destination['balance_after'], $currency['symbol'], $currency['decimal_places']) !!}</span>
            @else
                -
            @endif
        @endif
    @elseif('Opening balance' === $type)
        @if($account?->id == $source['id'])
            <span title="Opening balance, src">{!! formatAmountBySymbol($source['balance_after'], $currency['symbol'], $currency['decimal_places']) !!}</span>
        @elseif($account?->id == $destination['id'])
            <span title="Opening balance, dest">{!! formatAmountBySymbol($destination['balance_after'], $currency['symbol'], $currency['decimal_places']) !!}</span>
        @else
            -
        @endif
    @elseif('Transfer' === $type)
        @if($account?->id == $source['id'])
            <span title="Transfer, source">{!! formatAmountBySymbol($source['balance_after'], $currency['symbol'], $currency['decimal_places']) !!}</span>
        @else
            @if(null === $foreign['id'])
                <span title="Transfer, dest, normal currency">{!! formatAmountBySymbol($destination['balance_after'], $currency['symbol'], $currency['decimal_places']) !!}</span>
            @endif
            @if(null !== $foreign['id'])
                <span title="Transfer, dest, foreign currency">{!! formatAmountBySymbol($destination['balance_after'], $foreign['currency_symbol'], $foreign['decimal_places']) !!}</span>
            @endif
        @endif
    @else
        &nbsp;
    @endif
@endif
