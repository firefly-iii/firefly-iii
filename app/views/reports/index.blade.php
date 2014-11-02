@extends('layouts.default')
@section('content')
<div class="row">
 <div class="col-lg-6 col-md-6 col-sm-12">
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
</div>
@stop