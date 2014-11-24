@extends('layouts.default')
@section('content')
<div class="row">
<div class="col-lg-12 col-md-12 col-sm-12">
    @foreach($piggyBank->piggybankrepetitions as $rep)
    <div class="panel
    @if($today > $rep->startdate && $today < $rep->targetdate)
    panel-primary
    @else
    panel-default
    @endif
    ">
        <div class="panel-heading">
            Repetition from {{$rep->startdate->format('j F Y')}} to {{$rep->targetdate->format('j F Y')}}
        </div>
        <div class="panel-body">
            {{$piggyBank->reminder}}
        </div>
    </div>
    @endforeach
</div>
</div>
@stop