@extends('layouts.default')
@section('content')
{!! Breadcrumbs::renderIfExists(Route::getCurrentRoute()->getName()) !!}
<div class="row">
    <div class="col-lg-12 col-md-12 col-sm-12 col-xs-12">
        <p>
            <a href="#" class="btn btn-default" id="includeShared" style="display:none;">
                <i class="state-icon glyphicon glyphicon-unchecked"></i>
                Include shared asset accounts</a>
        </p>
    </div>
</div>
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

                    @foreach($months as $year => $entries)
                        <h5>{{$year}}</h5>
                        <ul>
                            @foreach($entries as $month)
                                <li><a href="{{route('reports.month',[$month['year'],$month['month']])}}">{{$month['formatted']}}</a></li>
                            @endforeach
                        </ul>
                    @endforeach

            </div>
        </div>
    </div>

    <div class="col-lg-4 col-md-4 col-sm-4">
        <div class="panel panel-default">
            <div class="panel-heading">
                Budget reports
            </div>
            <div class="panel-body">
                @foreach($months as $year => $entries)
                    <h5>{{$year}}</h5>
                    <ul>
                        @foreach($entries as $month)
                            <li><a href="{{route('reports.budget',[$month['year'],$month['month']])}}">{{$month['formatted']}}</a></li>
                        @endforeach
                    </ul>
                    @endforeach
            </div>
        </div>
    </div>
</div>
@stop
@section('scripts')
    <script type="text/javascript" src="js/reports.js"></script>
@stop