@extends('layouts.default')
@section('content')
{{ Breadcrumbs::renderIfExists(Route::getCurrentRoute()->getName(), $journal) }}
<div class="row">
    <div class="col-lg-6 col-md-6 col-sm-12">
        <div class="panel panel-default">
            <div class="panel-heading">
                Metadata
            </div>
            <div class="panel-body">
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
        </div>
        <!-- events, if present -->
        @if(count($journal->piggyBankEvents) > 0)
            <div class="panel panel-default">
                <div class="panel-heading">
                    Piggy banks
                </div>
                <div class="panel-body">
                    @include('list.piggy-bank-events',['events' => $journal->piggyBankEvents,'showPiggyBank' => true])
                </div>
            </div>
        @endif
        <div class="panel panel-default">
            <div class="panel-heading">
                Related transactions
            </div>
                @if($members->count() == 0)
                    <div class="panel-body">
                        <p>
                            <em>No related transactions</em>
                        </p>
                    </div>
                @else
                    <table class="table">
                    @foreach($members as $jrnl)
                        <tr>
                            <td><input type="checkbox" checked="checked" data-relatedto="{{$journal->id}}" data-id="{{$jrnl->id}}" class="unrelate-checkbox" /></td>
                            <td><a href="#">{{{$jrnl->description}}}</a></td>
                            <td>{{mfj($jrnl, $jrnl->getAmount())}}</td>
                        </tr>
                    @endforeach
                    </table>
                @endif
            <div class="panel-footer">
            <p>
                <a href="#" data-id="{{$journal->id}}" class="relateTransaction btn btn-default"><i data-id="{{$journal->id}}" class="fa fa-compress"></i> Relate to another transaction</a>
            </p>
            </div>
        </div>
    </div>
    <div class="col-lg-6 col-md-6 col-sm-12">

    @foreach($journal->transactions as $t)
        <div class="panel panel-default">
            <div class="panel-heading">
                <a href="{{route('accounts.show',$t->account->id)}}">{{{$t->account->name}}}</a><br /><small>{{{$t->account->accounttype->description}}}</small>
            </div>
            <div class="panel-body">
                <table class="table table-striped table-bordered">
                    <tr>
                        <td>Amount</td>
                        <td>{{mft($t)}}</td>
                    </tr>
                    <tr>
                        <td>New balance</td>
                        <td>{{mf($t->before)}} &rarr; {{mf($t->after)}}</td>
                    </tr>
                    @if(!is_null($t->description))
                    <tr>
                        <td>Description</td>
                        <td>{{{$t->description}}}</td>
                    </tr>
                    @endif
                </table>
            </div>
        </div>
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
{{HTML::script('assets/javascript/firefly/transactions.js')}}
{{HTML::script('assets/javascript/firefly/related-manager.js')}}
@stop
