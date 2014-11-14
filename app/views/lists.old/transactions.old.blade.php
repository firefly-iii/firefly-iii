<table class="table table-striped table-condensed" id="transactionTable">
<thead>
    <tr>
        <th colspan="2" id="empty1">A</th>
        <th>Date</th><!-- TODO remove me-->
        <th>Description</th>
        <th>Amount (&euro;)</th>
        <th>From</th>
        <th>To</th>
        <th id="empty2">B</th>
    </tr>
    </thead>
    <?php $expenses = 0;$incomes = 0;$transfers = 0; ?>
    @foreach($journals as $journal)
    @if(isset($journal->transactions[0]) && isset($journal->transactions[1]))
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
                <!-- recurring transaction -->
                @if(!is_null($journal->recurringTransaction))
                    <a href="{{route('recurring.show',$journal->recurring_transaction_id)}}" title="{{{$journal->recurringTransaction->name}}}"><span title="{{{$journal->recurringTransaction->name}}}" class="glyphicon glyphicon-refresh"></span></a>
                @endif
            </td>
            <td>
                {{$journal->date->format('d F Y')}}
            </td>
            <td><a href="{{route('transactions.show',$journal->id)}}" title="{{{$journal->description}}}">{{{$journal->description}}}</a></td>
            <td>
                @if($journal->transactiontype->type == 'Withdrawal')
                <span class="text-danger">{{mf($journal->transactions[1]->amount,false)}}</span>
                <?php $expenses -= $journal->transactions[1]->amount;?>
                @endif
                @if($journal->transactiontype->type == 'Deposit')
                <span class="text-success">{{mf($journal->transactions[1]->amount,false)}}</span>
                <?php $incomes += $journal->transactions[1]->amount;?>
                @endif
                @if($journal->transactiontype->type == 'Transfer')
                <span class="text-info">{{mf($journal->transactions[1]->amount,false)}}</span>
                <?php $transfers += $journal->transactions[1]->amount;?>
                @endif
            </td>
            <td>
                <a href="{{route('accounts.show',$journal->transactions[0]->account_id)}}">{{{$journal->transactions[0]->account->name}}}</a>
            </td>
            <td>
                <a href="{{route('accounts.show',$journal->transactions[1]->account_id)}}">{{{$journal->transactions[1]->account->name}}}</a>
            </td>
            <td>
                @if($journal->transactiontype->type != 'Opening balance')
                <div class="btn-group btn-group-xs">
                    <a href="{{route('transactions.edit',$journal->id)}}" class="btn btn-default">
                        <span class="glyphicon glyphicon-pencil"></span>
                        <a href="{{route('transactions.delete',$journal->id)}}" class="btn btn-danger">
                            <span class="glyphicon glyphicon-trash"></span>
                        </a>
                </div>
                @endif
            </td>
        </tr>
        @else
        <!--
        <tr class="danger">
        <td colspan="7">Invalid data found. Please delete this transaction and recreate it.</td>
        <td>
            <a href="{{route('transactions.delete',$journal->id)}}" class="btn btn-danger btn-xs">
                <span class="glyphicon glyphicon-trash"></span>
            </a>
        </td>
        </tr>
        -->
        @endif
    @endforeach
    @if(isset($sum) && $sum == true)
        @if($expenses != 0)
        <tr>
            <td colspan="4">Expenses:</td>
            <td colspan="4">{{mf($expenses)}}</td>
        </tr>
        @endif
        @if($incomes != 0)
        <tr>
            <td colspan="4">Incomes:</td>
            <td colspan="4">{{mf($incomes)}}</td>
        </tr>
        @endif
        @if($transfers != 0)
        <tr>
            <td colspan="4">Transfers:</td>
            <td colspan="4" class="text-info">{{mf($transfers,false)}}</td>
        </tr>
        @endif
    @endif


</table>