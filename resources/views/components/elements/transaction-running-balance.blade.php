{{-- RUNNING BALANCE --}}

@if(false === $balanceDirty && '' !== $destination['balance_after'] && '' !== $source['balance_after'])
    @if('Deposit' === $type)
        @if($source['id'] === $account?->id)
            <span title="Deposit, source">{!! format_amount_by_symbol($source['balance_after'], $currency['symbol'], $currency['decimal_places']) !!}</span>
        @else
            @if('Revenue account' === $source['type'])
                <span title="Deposit from revenue">{!! format_amount_by_symbol($destination['balance_after'], $currency['symbol'], $currency['decimal_places']) !!}</span>
            @else
                <span title="Deposit from liab">{!! format_amount_by_symbol($destination['balance_after'], $foreign['currency_symbol'], $foreign['decimal_places']) !!}</span>
            @endif
            {{-- if this is a deposit from revenue account, use the destination account currency? For #12043 and #12169. Otherwise, keep at source account -}}
            {{-- changed from normal currency_symbol to foreign_currency_symbol for #12043 --}}
        @endif
    @elseif('Withdrawal' === $type)
        {{-- withdrawal into a liability --}}
        @if(in_array($destination['type'], ['Mortgage','Debt','Loan'], true))
                @if($account?->id === $source['id'])
                    A <span title="Withdrawal, liab, source">{!! format_amount_by_symbol($source['balance_after'], $currency['symbol'], $currency['decimal_places']) !!}</span>
                @elseif($account?->id === $destination['id'])
                    B <span title="Withdrawal, liab, dest">{!! format_amount_by_symbol($destination['balance_after'], $currency['symbol'], $currency['decimal_places']) !!}</span>
                @else
                    -
                @endif
            {{-- withdrawal into an expense account --}}
        @else
            @if($account?->id === $source['id'])
                <span title="Withdrawal, source">{!! format_amount_by_symbol($source['balance_after'], $currency['symbol'], $currency['decimal_places']) !!}</span>
            @elseif($account?->id === $destination['id'])
                <span title="Withdrawal, dest">{!! format_amount_by_symbol($destination['balance_after'], $currency['symbol'], $currency['decimal_places']) !!}</span>
            @else
                -
            @endif
        @endif
    @elseif('Opening balance' === $type)
        @if($account?->id == $source['id'])
            <span title="Opening balance, src">{!! format_amount_by_symbol($source['balance_after'], $currency['symbol'], $currency['decimal_places']) !!}</span>
        @elseif($account?->id == $destination['id'])
            <span title="Opening balance, dest">{!! format_amount_by_symbol($destination['balance_after'], $currency['symbol'], $currency['decimal_places']) !!}</span>
        @else
            -
        @endif
    @elseif('Reconciliation' === $type)
        @if($account?->id == $source['id'])
            {{-- $source['balance_after'] --}}
            <span title="Opening balance, src">{!! format_amount_by_symbol('0', $currency['symbol'], $currency['decimal_places']) !!}</span>
        @elseif($account?->id == $destination['id'])
            <span title="Opening balance, dest">{!! format_amount_by_symbol($destination['balance_after'], $currency['symbol'], $currency['decimal_places']) !!}</span>
        @else
            -
        @endif
    @elseif('Transfer' === $type)
        @if($account?->id == $source['id'])
            <span title="Transfer, source">{!! format_amount_by_symbol($source['balance_after'], $currency['symbol'], $currency['decimal_places']) !!}</span>
        @else
            @if(null === $foreign['id'])
                <span title="Transfer, dest, normal currency">{!! format_amount_by_symbol($destination['balance_after'], $currency['symbol'], $currency['decimal_places']) !!}</span>
            @endif
            @if(null !== $foreign['id'])
                <span title="Transfer, dest, foreign currency">{!! format_amount_by_symbol($destination['balance_after'], $foreign['currency_symbol'], $foreign['decimal_places']) !!}</span>
            @endif
        @endif
    @else
        &nbsp;
    @endif
@endif
