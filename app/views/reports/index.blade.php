@extends('layouts.default')
@section('content')
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
            Budget reports
        </div>
        <div class="panel-body">
        <ul>
        @foreach($months as $month)
            <li><a href="{{route('reports.budgets',[$month['year'],$month['month']])}}">{{$month['formatted']}}</a></li>
        @endforeach
        </ul>
        </div>
    </div>
 </div>
 <div class="col-lg-4 col-md-4 col-sm-4">
     <div class="panel panel-default">
         <div class="panel-heading">
             Unbalanced transactions
         </div>
         <div class="panel-body">
         <ul>
         @foreach($months as $month)
             <li><a href="{{route('reports.unbalanced',[$month['year'],$month['month']])}}">{{$month['formatted']}}</a></li>
         @endforeach
         </ul>
         </div>
     </div>
  </div>
</div>
@stop