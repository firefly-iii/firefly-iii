@extends('layouts.default')
@section('content')
<div class="row">
    <div class="col-lg-12 col-md-12 col-sm-12">
        <h1>Firefly
            <small>Overview for account "{{{$account->name}}}"</small>
        </h1>
    </div>
</div>

@include('partials.date_nav')

<div class="row">
    <div class="col-lg-12 col-md-12 col-sm-12">
            <div id="chart"></div>
        </div>
    </div>

<div class="row">
    <div class="col-lg-12 col-md-12 col-sm-12">
        <h4>Transactions</h4>
    </div>
</div>

@stop
@section('scripts')
<script type="text/javascript">
    var accountID = {{$account->id}};
</script>
<script src="assets/javascript/highcharts.js"></script>
<script src="assets/javascript/highcharts-more.js"></script>
<script src="assets/javascript/highslide-full.min.js"></script>
<script src="assets/javascript/highslide.config.js"></script>
<script src="assets/javascript/accounts.js"></script>
@stop