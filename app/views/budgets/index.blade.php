@extends('layouts.default')
@section('content')
<div class="row">
    <div class="col-lg-12 col-md-12 col-sm-12">
        <h1>Firefly
            <small>Budgets and limits</small>
        </h1>
        <p class="text-info">
            These are your budgets and if set, their "limits". Firefly uses an "<a
                href="http://en.wikipedia.org/wiki/Envelope_System" class="text-success">envelope system</a>" for your
            budgets,
            which means that for each period of time (for example a month) a virtual "envelope" can be created
            containing a certain amount of money. Money spent within a budget is removed from the envelope.
        </p>
        <p>
            @if($group == 'budget')
                <a class="btn btn-default" href ="{{route('budgets.index','date')}}"><span class="glyphicon glyphicon-th-list"></span> Group by date</a>
            @else
                <a class="btn btn-default" href ="{{route('budgets.index','budget')}}"><span class="glyphicon glyphicon-th-list"></span> Group by budget</a>
            @endif
        </p>
    </div>
</div>
@if($group == 'budget')
    @include('budgets.index-budget')
@else
    @include('budgets.index-date')
@endif
@stop