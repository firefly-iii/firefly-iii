@extends('layouts.default')
@section('content')
{!! Breadcrumbs::renderIfExists(Route::getCurrentRoute()->getName()) !!}
<div class="row">
 <div class="col-lg-4 col-md-4 col-sm-4">
    <div class="panel panel-default">
        <div class="panel-heading">
            Yearly reports
        </div>
        <div class="panel-body">
            <ul>
                @foreach($years as $year)
                <li><a href="{{route('reports.year',$year)}}">{{$year}}</a></li>
                @endforeach
            </ul>
        </div>
    </div>
 </div>

    <div class="col-lg-4 col-md-4 col-sm-4">
        <div class="panel panel-default">
            <div class="panel-heading">
                Monthly reports
            </div>
            <div class="panel-body">
                <ul>
                    @foreach($months as $month)
                        <li><a href="{{route('reports.month',[$month['year'],$month['month']])}}">{{$month['formatted']}}</a></li>
                    @endforeach
                </ul>
            </div>
        </div>
    </div>

    <div class="col-lg-4 col-md-4 col-sm-4">
        <div class="panel panel-default">
            <div class="panel-heading">
                Budget reports
            </div>
            <div class="panel-body">
                <ul>
                    @foreach($months as $month)
                        <li><a href="{{route('reports.budget',[$month['year'],$month['month']])}}">{{$month['formatted']}}</a></li>
                    @endforeach
                </ul>
            </div>
        </div>
    </div>
</div>
@stop
