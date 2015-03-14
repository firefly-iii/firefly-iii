@extends('layouts.default')
@section('content')
{!! Breadcrumbs::renderIfExists(Route::getCurrentRoute()->getName(), $category) !!}
<div class="row">
    <div class="col-lg-6 col-md-6 col-sm-12">
        <div class="panel panel-default">
            <div class="panel-heading">
                <i class="fa fa-calendar fa-fw"></i>
                Overview
            </div>
            <div class="panel-body">
                <div id="periodOverview"></div>
            </div>
        </div>
    </div>
    <div class="col-lg-6 col-md-6 col-sm-12">
        <div class="panel panel-default">
            <div class="panel-heading">
                <i class="fa fa-calendar-o fa-fw"></i>
                Overview
            </div>
            <div class="panel-body">
                <div id="componentOverview"></div>
            </div>
        </div>
    </div>
</div>
<div class="row">
    <div class="col-lg-12 col-md-12">

         <div class="panel panel-default">
            <div class="panel-heading">
                <i class="fa fa-repeat fa-fw"></i>
                Transactions
            </div>
            <div class="panel-body">
                @include('list.journals-full')
            </div>
        </div>
    </div>
</div>

@stop
@section('scripts')
<script type="text/javascript">
    var categoryID = {{$category->id}};
    var currencyCode = '{{Amount::getCurrencyCode()}}';
</script>
<!-- load the libraries and scripts necessary for Google Charts: -->
<script type="text/javascript" src="https://www.google.com/jsapi"></script>
<script type="text/javascript" src="js/gcharts.options.js"></script>
<script type="text/javascript" src="js/gcharts.js"></script>
<script type="text/javascript" src="js/categories.js"></script>

@stop
