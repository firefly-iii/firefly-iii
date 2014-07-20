<table class="table table-bordered table-striped table-condensed">
    <tr>
        <th>&nbsp;</th>
        <th>Description</th>
        <th>Date</th>
        <th>Amount</th>
    </tr>
@foreach($transactions as $journal)
    <tr>

        <td>
            @if($journal->transactiontype->type == 'Withdrawal')
                <span class="glyphicon glyphicon-arrow-left" title="Withdrawal"></span>
            @endif
            @if($journal->transactiontype->type == 'Deposit')
                <span class="glyphicon glyphicon-arrow-right" title="Deposit"></span>
            @endif
            @if($journal->transactiontype->type == 'Transfer')
                <span class="glyphicon glyphicon-resize-full" title="Transfer"></span>
            @endif

        </td>
        <td><a href="{{route('transactions.show',$journal->id)}}">{{{$journal->description}}}</a></td>
        <td>{{$journal->date->format('jS M Y')}}</td>
        <td>
            @foreach($journal->transactions as $t)
                @if($t->account_id == $account->id)
                    {{mf($t->amount)}}
                @endif
            @endforeach
        </td>
    </tr>
@endforeach
</table>