<div class="list-group">
    @foreach($groups as $group)
        @foreach($group['transactions'] as $transaction)
    <a class="list-group-item" href="{{ route('transactions.show', [$transaction['transaction_group_id']]) }}">
        {{ $transaction['description'] }}
        <span class="text-end small">
            ({!! format_amount_by_code($transaction['amount'], $transaction['currency_code']) !!})
                </span>
    </a>
    @endforeach
    @endforeach
</div>
