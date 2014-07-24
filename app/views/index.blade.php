@extends('layouts.default')
@section('content')
<div class="row">
    <div class="col-lg-12 col-md-12 col-sm-12">
        <h1>Firefly
            @if($count > 0)
            <small>What's playing?</small>
            @endif
        </h1>
        @if($count > 0)
        <form role="form" class="form-horizontal">
            <div class="input-group">

                <?php $r = Session::get('range', '1M'); ?>
                <span class="input-group-btn input-group-btn">
                        <button name="range" value="1D" class="btn btn-default @if($r=='1D') btn-info @endif btn-sm"
                                type="submit">1D
                        </button>
                        <button name="range" value="1W" class="btn btn-default @if($r=='1W') btn-info @endif btn-sm"
                                type="submit">1W
                        </button>
                        <button name="range" value="1M" class="btn btn-default @if($r=='1M') btn-info @endif btn-sm"
                                type="submit">1M
                        </button>
                        <button name="range" value="3M" class="btn btn-default @if($r=='3M') btn-info @endif btn-sm"
                                type="submit">3M
                        </button>
                        <button name="range" value="6M" class="btn btn-default @if($r=='6M') btn-info @endif btn-sm"
                                type="submit">6M
                        </button>
                    </span>
                <input value="{{Session::get('start')->format('Y-m-d')}}" name="start" type="date"
                       style="width:15%;border-right:0;" class="form-control input-sm">
                <input value="{{Session::get('end')->format('Y-m-d')}}" name="end" type="date"
                       style="width:15%;border-right:0;" class="form-control input-sm">
                <button class="btn btn-default btn-sm @if($r=='custom') btn-info @endif" type="submit" name="range"
                        value="custom">Custom
                </button>
            </div>
        </form>
        @endif

    </div>
</div>
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
            <h4>{{{$data[1]->name}}}</h4>
            @include('transactions.journals',['transactions' => $data[0],'account' => $data[1]])
        </div>
        @endforeach
    </div>
    @endforeach
    @endif


    <div class="row">
        <div class="col-lg-12 col-md-12 col-sm-12">
            <h4>Budgets</h4>
            @foreach($budgets as $budget)
            <h5><a href="{{route('budgets.show',$budget->id)}}">{{{$budget->name}}}</a></h5>
            @if($budget->count == 0)
            <p>
                <small><em>No budget set for this period.</em></small>
            </p>
            @else
            @foreach($budget->limits as $limit)
            @foreach($limit->limitrepetitions as $rep)
            @if($rep->left() < 0)
            <!-- bar to display when over budget -->
            <div class="progress progress-striped">
                <div class="progress-bar progress-bar-warning" role="progressbar" aria-valuenow="{{$rep->spent_pct}}" aria-valuemin="0"
                     aria-valuemax="100" style="width: {{$rep->spent_pct}}%;min-width:30px;">{{mf($rep->amount,false)}}</div>
                <div class="progress-bar progress-bar-danger" role="progressbar" aria-valuenow="{{$rep->overspent_pct}}" aria-valuemin="0"
                         aria-valuemax="100" style="width: {{$rep->overspent_pct}}%;min-width:30px;">{{mf($rep->overspent,false)}}
                </div>
            </div>
            @else
            <!-- bar to display when UNDER budget -->
            <div class="progress progress-striped">
                <div class="progress-bar progress-bar-success" role="progressbar" aria-valuenow="{{$rep->spent_pct}}" aria-valuemin="0"
                     aria-valuemax="100" style="width: {{$rep->spent_pct}}%;min-width:80px;">{{mf($rep->spent,false)}}
                     </div>
                <div class="progress-bar progress-bar-info" role="progressbar" aria-valuenow="{{$rep->left_pct}}" aria-valuemin="0"
                     aria-valuemax="100" style="width: {{$rep->left_pct}}%;min-width:30px;">{{mf($rep->left,false)}}
                </div>
                </div>
            @endif


            @endforeach
            @endforeach
            @endif
            @endforeach
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