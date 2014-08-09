<table class="table table-striped table-condensed">
    <tr>
        <th colspan="2"></th>
        <th>Date</th>
        <th>Description</th>
        <th>Amount (&euro;)</th>
        <th>From</th>
        <th>To</th>
        <th></th>
    </tr>
    <?php $total = 0; ?>
    @foreach($journals as $journal)
    <tr
        @if(isset($highlight) && $highlight == $journal->id)
        class="success"
        @endif
        >
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
            @if($journal->transactiontype->type == 'Opening balance')
            <span class="glyphicon glyphicon-ban-circle" title="Opening balance"></span>
            @endif
        </td>
        <td>
            @foreach($journal->components as $component)
            @if($component->class == 'Budget')
            <a href="{{route('budgets.show',$component->id)}}?highlight={{$journal->id}}"><span class="glyphicon glyphicon-tasks" title="Budget: {{{$component->name}}}"></span></a>
            @endif
            @if($component->class == 'Category')
            <a href="{{route('categories.show',$component->id)}}?highlight={{$journal->id}}"><span class="glyphicon glyphicon-tag" title="Category: {{{$component->name}}}"></span></a>
            @endif
            @endforeach
        </td>
        <td>
            {{$journal->date->format('d F Y')}}
        </td>
        <td><a href="{{route('transactions.show',$journal->id)}}" title="{{{$journal->description}}}">{{{$journal->description}}}</a></td>
        <td>
            @if($journal->transactiontype->type == 'Withdrawal')
            <span class="text-danger">{{mf($journal->transactions[1]->amount,false)}}</span>
            <?php $total -= $journal->transactions[1]->amount;?>
            @endif
            @if($journal->transactiontype->type == 'Deposit')
            <span class="text-success">{{mf($journal->transactions[1]->amount,false)}}</span>
            @endif
            @if($journal->transactiontype->type == 'Transfer')
            <span class="text-info">{{mf($journal->transactions[1]->amount,false)}}</span>

            @endif
        </td>
        <td>
            <a href="{{route('accounts.show',$journal->transactions[0]->account_id)}}">{{{$journal->transactions[0]->account->name}}}</a>
        </td>
        <td>
            <a href="{{route('accounts.show',$journal->transactions[1]->account_id)}}">{{{$journal->transactions[1]->account->name}}}</a>
        </td>
        <td>
            <div class="btn-group btn-group-xs">
                <a href="{{route('transactions.edit',$journal->id)}}" class="btn btn-default">
                    <span class="glyphicon glyphicon-pencil"></span>
                    <a href="{{route('transactions.delete',$journal->id)}}" class="btn btn-danger">
                        <span class="glyphicon glyphicon-trash"></span>
                    </a>
            </div>
        </td>
    </tr>
    @endforeach
    @if(isset($sum) && $sum == true)
    <tr>
        <td colspan="4">Sum:</td>
        <td colspan="4">{{mf($total)}}</td>
    </tr>
    @endif

</table>