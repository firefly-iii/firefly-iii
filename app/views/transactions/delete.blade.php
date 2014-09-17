@extends('layouts.default')
@section('content')
<div class="row">
    <div class="col-lg-12 col-md-12 col-sm-12">
        <p class="lead">Remember that deleting something is permanent.</p>

    </div>

</div>

{{Form::open(['class' => 'form-horizontal','url' => route('transactions.destroy',$journal->id)])}}

<div class="row">
    <div class="col-lg-6">
        <p class="text-info">
            This form allows you to delete the transaction labelled "{{{$journal->description}}}".
        </p>
        <p class="text-danger">
            Are you sure?
        </p>

        <div class="form-group">
            <div class="col-sm-8">
                <input type="submit" name="submit" value="Delete transaction" class="btn btn-danger" />
                @if($journal->transactiontype->type == 'Withdrawal')
                    <a href="{{route('transactions.expenses')}}" class="btn-default btn">Cancel</a>
                @endif
                @if($journal->transactiontype->type == 'Deposit')
                    <a href="{{route('transactions.revenue')}}" class="btn-default btn">Cancel</a>
                @endif
                @if($journal->transactiontype->type == 'Transfer')
                    <a href="{{route('transactions.transfers')}}" class="btn-default btn">Cancel</a>
                @endif

            </div>
        </div>
    </div>

</div>


{{Form::close()}}

@stop
