@extends('layouts.default')
@section('content')
@include('partials.date_nav')
@if($count == 0)
<div class="row">
    <div class="col-lg-12 col-md-12 col-sm-12">
        <p class="lead">Welcome to Firefly III.</p>

        <p>
            To get get started, choose below:
        </p>
    </div>
</div>
<div id="something">Bla bla</div>
<div class="row">
    <div class="col-lg-6 col-md-6 col-sm-12">
        <h2><a href="{{route('migrate')}}">Migrate from Firefly II</a></h2>

        <p>
            Use this option if you have a JSON file from your current Firefly II installation.
        </p>
    </div>
    <div class="col-lg-6 col-md-6 col-sm-12">
        <h2><a href="#">Start from scratch</a></h2>

        <p>
            Use this option if you are new to Firefly (III).
        </p>
    </div>
    @else


    <!-- ACCOUNTS -->
    <div class="row">
        <div class="col-lg-12 col-md-12 col-sm-12">
            <div id="chart"></div>
        </div>
    </div>

    <!-- TRANSACTIONS -->
    @if(count($transactions) > 0)
    @foreach($transactions as $set)
    <div class="row">
        <?php $split = 12 / count($set); ?>
        @foreach($set as $data)
        <div class="col-lg-{{$split}} col-md-{{$split}}">
            <h4>
                <a href="{{route('accounts.show',$data[1]->id)}}?range={{Config::get('firefly.range_to_text.'.Session::get('range'))}}&amp;startdate={{Session::get('start')->format('Y-m-d')}}">{{{$data[1]->name}}}</a>
            </h4>

            @include('transactions.journals-small',['transactions' => $data[0],'account' => $data[1]])
            <div class="btn-group btn-group-xs">
                <a class="btn btn-default" href="{{route('transactions.create','withdrawal')}}?account={{$data[1]->id}}"><span class="glyphicon glyphicon-arrow-left" title="Withdrawal"></span> Add withdrawal</a>
                <a class="btn btn-default" href="{{route('transactions.create','deposit')}}?account={{$data[1]->id}}"><span class="glyphicon glyphicon-arrow-right" title="Deposit"></span> Add deposit</a>
                <a class="btn btn-default" href="{{route('transactions.create','transfer')}}?account={{$data[1]->id}}"><span class="glyphicon glyphicon-resize-full" title="Transfer"></span> Add transfer</a>
            </div>

        </div>
        @endforeach
    </div>
    @endforeach
    @endif


    <div class="row">
        <div class="col-lg-12 col-md-12 col-sm-12">
            <h4>Budgets</h4>

            <div id="budgets">

            </div>
        </div>
    </div>

    <div class="row">
        <div class="col-lg-12 col-md-12 col-sm-12">
            <div id="categories"></div>
        </div>
    </div>


    @endif

    @stop
    @section('scripts')
    <script src="assets/javascript/highcharts.js"></script>
    <script src="assets/javascript/highcharts-more.js"></script>
    <script src="assets/javascript/highslide-full.min.js"></script>
    <script src="assets/javascript/highslide.config.js"></script>
    <script src="assets/javascript/index.js"></script>
    @stop
    @section('styles')
    <link href="assets/css/highslide.css" rel="stylesheet">
    @stop