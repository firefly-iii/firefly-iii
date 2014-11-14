@extends('layouts.default')
@section('content')
<div class="row">
    <div class="col-lg-6 col-md-6 col-sm-12">
        <h3>Metadata</h3>
        <table class="table table-striped table-bordered">
            <tr>
                <td>Date</td>
                <td>{{{$journal->date->format('jS F Y')}}}</td>
            </tr>
            <tr>
                <td>Currency</td>
                <td>{{{$journal->transactioncurrency->code}}}</td>
            </tr>
            <tr>
                <td>Type</td>
                <td>{{{$journal->transactiontype->type}}}</td>
            </tr>
            <tr>
                <td>Completed</td>
                <td>
                    @if($journal->completed == 1)
                    <span class="text-success">Yes</span>
                    @else
                    <span class="text-danger">No</span>
                    @endif
                </td>
            </tr>
            @foreach($journal->budgets()->get() as $budget)
                            <tr>
                                <td>{{$budget->class}}</td>
                                <td><a href="{{route('budgets.show',$budget->id)}}">{{{$budget->name}}}</a></td>
                            </tr>
            @endforeach
            @foreach($journal->categories()->get() as $category)
                <tr>
                    <td>{{$category->class}}</td>
                    <td><a href="{{route('categories.show',$category->id)}}">{{{$category->name}}}</a></td>
                </tr>
            @endforeach

        </table>
    </div>

    <div class="col-lg-6 col-md-6 col-sm-12">
        <h3>Transactions</h3>
        @foreach($journal->transactions as $t)
        <h4><a href="{{route('accounts.show',$t->account->id)}}">{{{$t->account->name}}}</a><br /><small>{{{$t->account->accounttype->description}}}</small></h4>
        <table class="table table-striped table-bordered">
            <tr>
                <td>Amount</td>
                <td>{{mf($t->amount)}}</td>
            </tr>
            <tr>
                <td>New balance</td>
                <td>{{mf($t->account->balanceBeforeJournal($journal))}} &rarr; {{mf($t->account->balanceBeforeJournal($journal) + $t->amount)}}</td>
            </tr>
            @if(!is_null($t->description))
            <tr>
                <td>Description</td>
                <td>{{{$t->description}}}</td>
            </tr>
            @endif
        </table>
        @endforeach

    </div>
</div>
<div class="row">
    <div class="col-lg-6 col-md-6 col-sm-12">
        <div class="btn-group">
        <a class="btn btn-default" href="{{route('transactions.edit',$journal->id)}}"><span class="glyphicon glyphicon-pencil"></span> Edit</a> <a href="{{route('transactions.delete',$journal->id)}}" class="btn btn-danger"><span class="glyphicon glyphicon-trash"></span> Delete</a>
        </div>
    </div>
</div>


@stop
@section('scripts')
@stop