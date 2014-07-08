@extends('layouts.default')
@section('content')
<div class="row">
    <div class="col-lg-12 col-md-12 col-sm-12">
        <h1>Firefly
            @if($count > 0)
            <small>What's playing?</small>
            @endif
        </h1>
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
            <h4>{{{$account->name}}} chart</h4>
            <div id="chart_{{{$account->id}}}" data-id="{{{$account->id}}}" class="homeChart" data-title="{{{$account->name}}}"></div>
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
    </div><div class="row" style="border-top:1px #eee solid;">
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
            <h4>Beneficiaries</h4>
        </div>
        <div class="col-lg-4 col-sm-6 col-md-6">
            <h4>Categories</h4>
        </div>
        <div class="col-lg-4 col-sm-6 col-md-6">
            <h4>Budgets</h4>
        </div>
    </div>


@endif

@stop
@section('scripts')
    <script src="https://www.google.com/jsapi"></script>
    <script src="assets/javascript/charts.js"></script>
    <script src="assets/javascript/index.js"></script>
@stop