@extends('layouts.default')
@section('content')
<div class="row">
    <div class="col-lg-12 col-md-12 col-sm-12">
        <h1>Firefly
            <small>Delete envelope</small>
        </h1>
        <p class="lead">Remember that deleting something is permanent.</p>
        <p class="text-info">
            This form allows you to delete the envelope for budget {{{$limit->budget->name}}}, with a content of
            {{mf($limit->amount,false)}}
            @if($limit->repeats == 0)
                in {{$limit->limitrepetitions[0]->startdate->format('M Y')}} ({{$limit->repeat_freq}}).
            @endif
        </p>
    </div>

</div>

{{Form::open(['class' => 'form-horizontal','url' => route('budgets.limits.destroy',$limit->id)])}}

<div class="row">
    <div class="col-lg-12">
        <p class="text-info">
            Destroying an envelope does not remove any transactions from the budget.
        </p>
        <p class="text-danger">
            Are you sure?
        </p>

        <div class="form-group">
            <div class="col-sm-8">
                <input type="submit" name="submit" value="Remove envelope" class="btn btn-danger" />
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
