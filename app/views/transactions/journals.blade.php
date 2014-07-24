<table class="table table-bordered table-striped table-condensed">
    <tr>
        <th><small>&nbsp;</small></th>
        <th><small>Description</small></th>
        <th style="min-width:100px;"><small>Date</small></th>
        <th style="min-width:80px;"><small>Amount</small></th>
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
        <td><small><a href="{{route('transactions.show',$journal->id)}}">{{{$journal->description}}}</a></small></td>
        <td><small>{{$journal->date->format('jS M Y')}}</small></td>
        <td><small>
            @foreach($journal->transactions as $t)
                @if($t->account_id == $account->id)
                    {{mf($t->amount)}}
                @endif
            @endforeach
            </small>
        </td>
    </tr>
@endforeach
</table>