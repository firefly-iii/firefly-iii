@if(is_object($journals) && method_exists($journals, 'links'))
{{$journals->links()}}
@endif
<table class="table table-striped table-bordered">
    <tr>
        <th colspan="2">&nbsp;</th>
        <th>Description</th>
        <th>Amount</th>
        <th>Date</th>
        <th>From</th>
        <th>To</th>
        @if(!isset($hideBudget) || (isset($hideBudget) && $hideBudget=== false))
            <th><i class="fa fa-tasks fa-fw" title="Budget"></i></th>
        @endif
        @if(!isset($hideCategory) || (isset($hideCategory) && $hideCategory=== false))
            <th><i class="fa fa-bar-chart fa-fw" title="Category"></i></th>
        @endif
        @if(!isset($hideBill) || (isset($hideBill) && $hideBill=== false))
            <th><i class="fa fa-fw fa-rotate-right" title="Bill"></i></th>
        @endif
    </tr>
    @foreach($journals as $journal)
    @if(!isset($journal->transactions[1]) || !isset($journal->transactions[0]))
        <tr>
            <td>
                <div class="btn-group btn-group-xs">
                    <a href="{{route('transactions.delete',$journal->id)}}" class="btn btn-xs btn-danger"><span class="glyphicon glyphicon-trash"></span></a>
                </div>
            </td>
            <td>&nbsp;</td>
            <td>{{{$journal->description}}}</td>
            <td colspan="7"><em>Invalid journal: Found {{$journal->transactions()->count()}} transaction(s)</td>
        </tr>
    @else
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
            <span class="text-danger">{{mft($journal->transactions[1],false)}}</span>
            @endif
            @if($journal->transactiontype->type == 'Deposit')
            <span class="text-success">{{mft($journal->transactions[1],false)}}</span>
            @endif
            @if($journal->transactiontype->type == 'Transfer')
            <span class="text-info">{{mft($journal->transactions[1],false)}}</span>
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
        @if(!isset($hideCategory) || (isset($hideCategory) && $hideCategory=== false))
        <td>
        <?php $category = isset($journal->categories[0]) ? $journal->categories[0] : null; ?>
            @if($category)
                <a href="{{route('categories.show',$category->id)}}">{{{$category->name}}}</a>
            @endif
        </td>
        @endif
        @if(!isset($hideBill) || (isset($hideBill) && $hideBill=== false))
        <td>
            @if($journal->bill)
                <a href="{{route('bills.show',$journal->bill_id)}}">{{{$journal->bill->name}}}</a>
            @endif
        </td>
        @endif


    </tr>
        @endif

    @endforeach
</table>

@if(is_object($journals) && method_exists($journals, 'links'))
{{$journals->links()}}
@endif
