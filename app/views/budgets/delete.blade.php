@extends('layouts.default')
@section('content')
<div class="row">
    <div class="col-lg-12 col-md-12 col-sm-12">
        <p class="lead">
            Remember that deleting something is permanent.
        </p>
    </div>
</div>

{{Form::open(['class' => 'form-horizontal','url' => route('budgets.destroy',$budget->id)])}}
{{Form::hidden('from',e(Input::get('from')))}}
<div class="row">
    <div class="col-lg-12 col-md-12 col-sm-12">
        @if($budget->transactionjournals()->count() > 0)
        <p class="text-info">

            Account "{{{$budget->name}}}" still has {{$budget->transactionjournals()->count()}} transaction(s) associated to it.
            These will NOT be deleted but will lose their connection to the budget.
        </p>
        @endif

        <p class="text-danger">
            Press "Delete permanently" If you are sure you want to delete "{{{$budget->name}}}".
        </p>
    </div>

</div>

<div class="row">
    <div class="col-lg-6">
        <div class="form-group">
            <div class="col-sm-8">
                <button type="submit" class="btn btn-default btn-danger">Delete permanently</button>
                @if(Input::get('from') == 'date')
                    <a href="{{route('budgets.index')}}" class="btn-default btn">Cancel</a>
                @else
                    <a href="{{route('budgets.index.budget')}}" class="btn-default btn">Cancel</a>
                @endif
            </div>
        </div>
    </div>
</div>


{{Form::close()}}
@stop