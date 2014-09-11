@extends('layouts.default')
@section('content')
<div class="row">
    <div class="col-lg-12 col-md-12 col-sm-12">
        <p class="lead">
            Remember that deleting something is permanent.
        </p>
    </div>
</div>

{{Form::open(['class' => 'form-horizontal','url' => route('accounts.destroy',$account->id)])}}
<div class="row">
    <div class="col-lg-12 col-md-12 col-sm-12">
        @if($account->transactions()->count() > 0)
        <p class="text-info">
            Account "{{{$account->name}}}" still has {{$account->transactions()->count()}} transaction(s) associated to it.
            These will be deleted as well.
        </p>
        @endif

        <p class="text-danger">
            Press "Delete permanently" If you are sure you want to delete "{{{$account->name}}}".
        </p>
    </div>

</div>

<div class="row">
    <div class="col-lg-6">
        <div class="form-group">
            <div class="col-sm-8">
                <button type="submit" class="btn btn-default btn-danger">Delete permanently</button>
                @if($account->accountType->type == 'Asset account' || $account->accountType->type == 'Default account')
                    <a href="{{route('accounts.asset')}}" class="btn-default btn">Cancel</a >
                @endif
                @if($account->accountType->type == 'Expense account' || $account->accountType->type == 'Beneficiary account')
                    <a href="{{route('accounts.expense')}}" class="btn-default btn">Cancel</a >
                @endif
                @if($account->accountType->type == 'Revenue account')
                <a href="{{route('accounts.revenue')}}" class="btn-default btn">Cancel</a >
                @endif
            </div>
        </div>
    </div>
</div>


{{Form::close()}}
@stop