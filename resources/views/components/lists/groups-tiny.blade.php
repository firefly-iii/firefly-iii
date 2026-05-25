<div class="list-group">
    @foreach($transactions as $transaction)

    <a class="list-group-item list-group-item-action d-flex justify-content-between align-items-center" href="{{ route('transactions.show', [$transaction['transaction_group_id']]) }}">
        @if('' !== (string) $transaction['transaction_group_title'])
        {{ $transaction['transaction_group_title'] }}:
        @endif
        {{ $transaction['description'] }}
            <span class="small">
                <x-generic.amount :transaction="$transaction" />
            </span>
    </a>
    @endforeach
</div>
