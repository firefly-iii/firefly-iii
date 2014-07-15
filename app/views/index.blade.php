@extends('layouts.default')
@section('content')
<div class="row">
    <div class="col-lg-12 col-md-12 col-sm-12">
        <h1>Firefly
            @if($count > 0)
            <small>What's playing?</small>
            @endif
        </h1>
        <form role="form" class="form-horizontal">
                <div class="input-group">

                    <?php $r = Session::get('range','1M');?>
                    <span class="input-group-btn input-group-btn">
                        <button name="range" value="1D" class="btn btn-default @if($r=='1D') btn-info @endif btn-sm" type="submit">1D</button>
                        <button name="range" value="1W" class="btn btn-default @if($r=='1W') btn-info @endif btn-sm" type="submit">1W</button>
                        <button name="range" value="1M" class="btn btn-default @if($r=='1M') btn-info @endif btn-sm" type="submit">1M</button>
                        <button name="range" value="3M" class="btn btn-default @if($r=='3M') btn-info @endif btn-sm" type="submit">3M</button>
                        <button name="range" value="6M" class="btn btn-default @if($r=='6M') btn-info @endif btn-sm" type="submit">6M</button>
                    </span>
                    <input value="{{Session::get('start')->format('Y-m-d')}}" name="start" type="date" style="width:15%;" class="form-control input-sm">
                    <input value="{{Session::get('end')->format('Y-m-d')}}" name="end" type="date" style="width:15%;" class="form-control input-sm">
                    <button class="btn btn-default btn-sm @if($r=='custom') btn-info @endif"  type="submit" name="range" value="custom">Custom</button>

                </div>
        </form>

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
    @foreach($accounts as $index => $account)
        <div class="col-lg-6">
            <div id="chart_{{{$account->id}}}" data-id="{{{$account->id}}}" style="width:100%;" class="homeChart" data-title="{{{$account->name}}}"></div>
            <p>
                Go to <a href="#" title="Overview for {{{$account->name}}}">{{{$account->name}}}</a>
            </p>

        </div>
        @if($index % 2 == 1)
        </div><div class="row">
        @endif
    @endforeach
    </div>

    <!-- TRANSACTIONS -->
    <div class="row">
        @foreach($accounts as $index => $account)
        <div class="col-lg-6">
            <h4>{{$account->name}}</h4>
            @include('transactions.journals',['journals' => $account->transactionList])
        </div>
        @if($index % 2 == 1)
    </div><div class="row">
        @endif
        @endforeach
    </div>


    <!-- week / month / year navigation -->
    <div class="row">
        <div class="col-lg-2 col-sm-6 col-md-2">
            <a href="#" class="btn btn-default btn-xs">Previous [period]</a>
        </div>

        <div class="col-lg-offset-8 col-lg-2 col-sm-6 col-md-offset-8 col-md-2" style="text-align: right;">
            <a href="#" class="btn btn-default btn-xs">Next [period]</a>
        </div>
    </div>

    <!-- Beneficiaries, categories and budget pie charts: -->
    <div class="row">
        <div class="col-lg-4 col-sm-6 col-md-6">
            <div style="width:80%;margin:0 auto;" id="beneficiaryChart"></div>
        </div>
        <div class="col-lg-4 col-sm-6 col-md-6">
            <div style="width:80%;margin:0 auto;" id="categoryChart"></div>
        </div>
        <div class="col-lg-4 col-sm-6 col-md-6">
            <div style="width:80%;margin:0 auto;" id="budgetChart"></div>
        </div>
    </div>
    <br /><br /><br /><br /><br />


@endif

@stop
@section('scripts')
    <script src="assets/javascript/highcharts.js"></script>
    <script src="assets/javascript/index.new.js"></script>
@stop