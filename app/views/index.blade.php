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
        <h2><a href="{{route('accounts.create')}}">Start from scratch</a></h2>

        <p>
            Use this option if you are new to Firefly (III).
        </p>
    </div>
    @else


    <!-- ACCOUNTS -->
    <div class="row">
        <div class="col-lg-8 col-md-12 col-sm-12">
            <div class="panel panel-default">
                <div class="panel-heading">
                    <i class="fa fa-credit-card fa-fw"></i> <a href="#">Your accounts</a>
                </div>
                <div class="panel-body">
                    <div id="accounts-chart" style="height:300px;"></div>
                </div>
            </div>
            <div class="panel panel-default">
                <div class="panel-heading">
                    <i class="fa fa-tasks fa-fw"></i> <a href="{{route('budgets.index.date')}}">Budgets and spending</a>
                </div>
                <div class="panel-body">
                    <div id="budgets"></div>
                </div>
            </div>
            <div class="panel panel-default">
                <div class="panel-heading">
                    <i class="fa fa-bar-chart fa-fw"></i> <a href="{{route('categories.index')}}">Categories</a>
                </div>
                <div class="panel-body">
                    <div id="categories"></div>
                </div>

            </div>



        </div>
        <div class="col-lg-4 col-md-6 col-sm-12">
            <!-- time based navigation -->
            <div class="panel panel-default">
                <div class="panel-heading">
                    <i class="fa fa-clock-o fa-fw"></i>
                    {{{\Session::get('period')}}}
                </div>
                <div class="panel-body">
                    <div class="btn-group btn-group-sm btn-group-justified">
                        <a class="btn btn-default" href="{{route('sessionPrev')}}"><i class="fa fa-arrow-left"></i> {{{\Session::get('prev')}}}</a>
                        <a class="btn btn-default" href="{{route('sessionNext')}}">{{{\Session::get('next')}}} <i class="fa fa-arrow-right"></i></a>
                    </div>
                </div>
            </div>

            <!-- TRANSACTIONS -->
            @foreach($transactions as $data)
            <div class="panel panel-default">
                <div class="panel-heading">
                    <i class="fa fa-money fa-fw"></i>
                    <a href="{{route('accounts.show',$data[1]->id)}}">{{{$data[1]->name}}}</a>
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