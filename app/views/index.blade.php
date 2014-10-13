@extends('layouts.default')
@section('content')
{{ Breadcrumbs::render('home') }}
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
        <h2><a href="{{route('migrate.index')}}">Migrate from Firefly II</a></h2>

        <p>
            Use this option if you have a JSON file from your current Firefly II installation.
        </p>
    </div>
    <div class="col-lg-6 col-md-6 col-sm-12">
        <h2><a href="{{route('accounts.create','asset')}}">Start from scratch</a></h2>

        <p>
            Use this option if you are new to Firefly (III).
        </p>
    </div>
    @else



    <div class="row">
        <div class="col-lg-8 col-md-12 col-sm-12">
            <!-- ACCOUNTS -->
            <div class="panel panel-default">
                <div class="panel-heading">
                    <i class="fa fa-credit-card fa-fw"></i> <a href="#">Your accounts</a>
                </div>
                <div class="panel-body">
                    <div id="accounts-chart" style="height:300px;"></div>
                </div>
            </div>
            <!-- BUDGETS -->
            <div class="panel panel-default">
                <div class="panel-heading">
                    <i class="fa fa-tasks fa-fw"></i> <a href="{{route('budgets.index.date')}}">Budgets and spending</a>
                </div>
                <div class="panel-body">
                    <div id="budgets"></div>
                </div>
            </div>
            <!-- CATEGORIES -->
            <div class="panel panel-default">
                <div class="panel-heading">
                    <i class="fa fa-bar-chart fa-fw"></i> <a href="{{route('categories.index')}}">Categories</a>
                </div>
                <div class="panel-body">
                    <div id="categories"></div>
                </div>
            </div>
                    <div class="panel panel-default">
                        <div class="panel-heading">
                            <i class="fa fa-line-chart"></i> Savings
                        </div>
                        <div class="panel-body">
                            Bla bla
                        </div>
                    </div>



        </div>
        <div class="col-lg-4 col-md-6 col-sm-12">
            <!-- time based navigation -->
            @include('partials.date_nav')

            <!-- REMINDERS -->
            <div class="panel panel-default">
                <div class="panel-heading">
                    <i class="fa fa-line-chart"></i> Recurring transactions
                </div>
                <div class="panel-body">
                    <div id="recurring"></div>
                </div>
            </div>

            <!-- TRANSACTIONS -->
            @foreach($transactions as $data)
            <div class="panel panel-default">
                <div class="panel-heading">
                    <i class="fa fa-money fa-fw"></i>
                    <a href="{{route('accounts.show',$data[1]->id)}}">{{{$data[1]->name}}}</a>


                    <!-- ACTIONS MENU -->
                    <div class="pull-right">
                        <div class="btn-group">
                            <button type="button" class="btn btn-default btn-xs dropdown-toggle" data-toggle="dropdown">
                                Actions
                                <span class="caret"></span>
                            </button>
                            <ul class="dropdown-menu pull-right" role="menu">
                                <li><a href="{{route('transactions.create','withdrawal')}}?account_id={{{$data[1]->id}}}"><i class="fa fa-long-arrow-left fa-fw"></i> New withdrawal</a></li>
                                <li><a href="{{route('transactions.create','deposit')}}?account_id={{{$data[1]->id}}}"><i class="fa fa-long-arrow-right fa-fw"></i> New deposit</a></li>
                                <li><a href="{{route('transactions.create','transfer')}}?account_from_id={{{$data[1]->id}}}"><i class="fa fa-arrows-h fa-fw"></i> New transfer</a></li>
                            </ul>
                        </div>
                    </div>



                </div>
                <div class="panel-body">
                    @include('transactions.journals-small-index',['transactions' => $data[0],'account' => $data[1]])
                </div>
            </div>
            @endforeach
        </div>
    </div>

    @endif

    @stop
    @section('scripts')
    {{HTML::script('assets/javascript/highcharts/highcharts.js')}}
    {{HTML::script('assets/javascript/firefly/index.js')}}
    @stop
    @section('styles')
    {{HTML::style('assets/stylesheets/highslide/highslide.css')}}
    @stop