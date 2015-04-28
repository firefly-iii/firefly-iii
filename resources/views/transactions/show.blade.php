@extends('layouts.default')
@section('content')
{!! Breadcrumbs::renderIfExists(Route::getCurrentRoute()->getName(), $journal) !!}
<div class="row">
    <div class="col-lg-6 col-md-6 col-sm-12">
        <div class="panel panel-default">
            <div class="panel-heading">
                <i class="fa fa-info-circle fa-fw"></i>
                Metadata
            </div>
                <table class="table table-striped table-bordered">
                    <tr>
                        <td>Date</td>
                        <td>{{{$journal->date->format('jS F Y')}}}</td>
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
                                        <td>Budget</td>
                                        <td><a href="{{route('budgets.show',$budget->id)}}">{{{$budget->name}}}</a></td>
                                    </tr>
                    @endforeach
                    @foreach($journal->categories()->get() as $category)
                        <tr>
                            <td>Category</td>
                            <td><a href="{{route('categories.show',$category->id)}}">{{{$category->name}}}</a></td>
                        </tr>
                    @endforeach

                </table>
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
    </div>
    <div class="col-lg-6 col-md-6 col-sm-12">

    @foreach($journal->transactions as $t)
        <div class="panel panel-default">
            <div class="panel-heading">
                @if($t->account->accounttype->type == 'Asset account')
                    <i class="fa fa-money fa-fw"></i>
                @endif
                @if($t->account->accounttype->type == 'Default account')
                    <i class="fa fa-money fa-fw"></i>
                @endif
                @if($t->account->accounttype->type == 'Expense account')
                    <i class="fa fa-shopping-cart fa-fw"></i>
                @endif
                @if($t->account->accounttype->type == 'Beneficiary account')
                    <i class="fa fa-shopping-cart fa-fw"></i>
                @endif

                @if($t->account->accounttype->type == 'Revenue account')
                    <i class="fa fa-download fa-fw"></i>
                @endif
                <a href="{{route('accounts.show',$t->account->id)}}">{{{$t->account->name}}}</a><br /><small>{{{$t->account->accounttype->description}}}</small>
            </div>
                <table class="table table-striped table-bordered">
                    <tr>
                        <td>Amount</td>
                        <td>{!! Amount::formatTransaction($t) !!}</td>
                    </tr>
                    <tr>
                        <td>New balance</td>
                        <td>{!! Amount::format($t->before) !!} &rarr; {!! Amount::format($t->after) !!}</td>
                    </tr>
                    @if(!is_null($t->description))
                    <tr>
                        <td>Description</td>
                        <td>{{{$t->description}}}</td>
                    </tr>
                    @endif
                </table>
        </div>
    @endforeach
    </div>
</div>

<div class="row">
    <div class="col-lg-6 col-md-6 col-sm-12">
        <div class="btn-group">
        <a class="btn btn-default" href="{{route('transactions.edit',$journal->id)}}"><i class="fa fa-fw fa-pencil"></i> Edit</a> <a href="{{route('transactions.delete',$journal->id)}}" class="btn btn-danger"><span class="glyphicon glyphicon-trash"></span> Delete</a>
        </div>
    </div>
</div>

@stop
@section('scripts')
<script type="text/javascript" src="js/transactions.js"></script>
<script type="text/javascript" src="js/related-manager.js"></script>
@stop
