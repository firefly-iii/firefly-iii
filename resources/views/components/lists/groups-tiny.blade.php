<table class="table">
    @foreach($transactions as $transaction)
<tr>
    <td class="w-10">
        <x-elements.transaction-type-icon :type="$transaction['transaction_type_type']" />
    </td>
    <td>
        @if('' !== (string) $transaction['transaction_group_title'])
            <small>{{ $transaction['transaction_group_title'] }}:</small>
        @endif
        <a class="" href="{{ route('transactions.show', [$transaction['transaction_group_id']]) }}">
            {{ $transaction['description'] }}
        </a>
    </td>
    <td class="text-end w-30">
        <span class="small">
            <x-generic.amount :transaction="$transaction" />
        </span>
    </td>
</tr>
    @endforeach
</table>
