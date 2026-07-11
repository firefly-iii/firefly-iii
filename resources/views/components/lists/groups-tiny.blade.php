<table class="table">
    @foreach($transactions as $transaction)
<tr>
    <td style="width:10%;">
        <x-elements.transaction-type-icon :type="$transaction['transaction_type_type']" />
    </td>
    <td>
        <a class="" href="{{ route('transactions.show', [$transaction['transaction_group_id']]) }}">
            @if('' !== (string) $transaction['transaction_group_title'])
                {{ $transaction['transaction_group_title'] }}:
                @endif
                {{ $transaction['description'] }}
        </a>
    </td>
    <td class="text-end" style="width:30%;">
        <span class="small">
            <x-generic.amount :transaction="$transaction" />
        </span>
    </td>
</tr>
    @endforeach



        <span>



    </a>
</div>
</table>
