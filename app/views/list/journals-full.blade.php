@if(is_object($journals))
{{$journals->links()}}
@endif
<table class="table table-striped table-bordered">
    <tr>
        <th>&nbsp;</th>
        <th>&nbsp;</th>
        <th>Description</th>
        <th>Amount</th>
        <th>Date</th>
        <th>From</th>
        <th>To</th>
        @if(!isset($hideBudget) || (isset($hideBudget) && $hideBudget=== false))
            <th><i class="fa fa-tasks fa-fw" title="Budget"></i></th>
        @endif
        <th><i class="fa fa-bar-chart fa-fw" title="Category"></i></th>
        <th><i class="fa fa-fw fa-rotate-right" title="Recurring transaction"></i></th>
    </tr>
    @foreach($journals as $journal)
    <tr>
        <td>
            <div class="btn-group btn-group-xs">
                <a href="{{route('transactions.edit',$journal->id)}}" class="btn btn-xs btn-default"><span class="glyphicon glyphicon-pencil"></span></a>
                <a href="{{route('transactions.delete',$journal->id)}}" class="btn btn-xs btn-danger"><span class="glyphicon glyphicon-trash"></span></a>
            </div>
        </td>
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
            <a href="{{route('transactions.show',$journal->id)}}" title="{{{$journal->description}}}">{{{$journal->description}}}</a>
        </td>
        <td>
            @if($journal->transactiontype->type == 'Withdrawal')
            <span class="text-danger">{{mf($journal->transactions[1]->amount,false)}}</span>
            @endif
            @if($journal->transactiontype->type == 'Deposit')
            <span class="text-success">{{mf($journal->transactions[1]->amount,false)}}</span>
            @endif
            @if($journal->transactiontype->type == 'Transfer')
            <span class="text-info">{{mf($journal->transactions[1]->amount,false)}}</span>
            @endif
        </td>
        <td>
            {{$journal->date->format('j F Y')}}
        </td>
        <td>
            @if($journal->transactions[0]->account->accounttype->description == 'Cash account')
                <span class="text-success">(cash)</span>
            @else
                <a href="{{route('accounts.show',$journal->transactions[0]->account_id)}}">{{{$journal->transactions[0]->account->name}}}</a>
            @endif
        </td>
        <td>
            @if($journal->transactions[1]->account->accounttype->description == 'Cash account')
                <span class="text-success">(cash)</span>
            @else
                <a href="{{route('accounts.show',$journal->transactions[1]->account_id)}}">{{{$journal->transactions[1]->account->name}}}</a>
            @endif
        </td>
        @if(!isset($hideBudget) || (isset($hideBudget) && $hideBudget=== false))
            <td>
            <?php $budget = isset($journal->budgets[0]) ? $journal->budgets[0] : null; ?>
                @if($budget)
                    <a href="{{route('budgets.show',$budget->id)}}">{{{$budget->name}}}</a>
                @endif
            </td>
        @endif
        <td>
        <?php $category = isset($journal->categories[0]) ? $journal->categories[0] : null; ?>
            @if($category)
                <a href="{{route('categories.show',$category->id)}}">{{{$category->name}}}</a>
            @endif
        </td>
        <td>
        @if($journal->recurringTransaction)
            <a href="{{route('recurring.show',$journal->recurring_transaction_id)}}">{{{$journal->recurringTransaction->name}}}</a>
        @endif
        </td>


    </tr>
    @endforeach
</table>

@if(is_object($journals))
{{$journals->links()}}
@endif