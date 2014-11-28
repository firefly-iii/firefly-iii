@extends('layouts.default')
@section('content')
@if(count($withdrawals) == 0 && count($deposits) == 0)
<div class="row">
    <div class="col-lg-4 col-md-4 col-sm-6">
        <p class="text-success">Everything accounted for.</p>
    </div>
</div>
@endif
@if(count($withdrawals) > 0)
<div class="row">
    <div class="col-lg-4 col-md-4 col-sm-6">
        <h3>Withdrawals</h3>
    </div>
</div>

<div class="row">
    @foreach($withdrawals as $journal)
        <div class="col-lg-4 col-md-4 col-sm-6">
            <div class="panel panel-danger">
                <div class="panel-heading">
                    <a href="{{route('transactions.show',$journal->id)}}">{{{$journal->description}}}</a>
                </div>
                <div class="panel-body">
                    <p>Spent {{mf($journal->getAmount())}}</p>
                    <p class="text-danger">No counter transaction!</p>
                </div>
            </div>
        </div>
    @endforeach
</div>
@endif
@if(count($deposits) > 0)
<div class="row">
    <div class="col-lg-4 col-md-4 col-sm-6">
        <h3>Deposits</h3>
    </div>
</div>
<div class="row">
    @foreach($deposits as $journal)
        <div class="col-lg-4 col-md-4 col-sm-6">
            <div class="panel panel-danger">
                <div class="panel-heading">
                    <a href="{{route('transactions.show',$journal->id)}}">{{{$journal->description}}}</a>
                </div>
                <div class="panel-body">
                    <p>Received {{mf($journal->getAmount())}}</p>
                    <p class="text-danger">No counter transaction!</p>
                </div>
            </div>
        </div>
    @endforeach
</div>
@endif




@stop
@section('scripts')
@stop