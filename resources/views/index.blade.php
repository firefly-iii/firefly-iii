@extends('layouts.default')
@section('content')
{!! Breadcrumbs::renderIfExists() !!}
@if($count == 0)
<div class="row">
    <div class="col-lg-12 col-md-12 col-sm-12">
        <p class="lead">Welcome to Firefly III.</p>

        <p>
            Create a new asset account to get started.
        </p>
    </div>
</div>
<div class="row">
    <div class="col-lg-6 col-md-6 col-sm-12">
        <h2><a href="{{route('accounts.create','asset')}}">Start from scratch</a></h2>
    </div>
    @else

<!-- fancy new boxes -->
    @include('partials.boxes')




<div class="row">
    <div class="col-lg-8 col-md-12 col-sm-12">
        <!-- ACCOUNTS -->
        <div class="panel panel-default">
            <div class="panel-heading">
                <i class="fa fa-credit-card fa-fw"></i> <a href="#">Your accounts</a>
            </div>
            <div class="panel-body">
                <div id="accounts-chart"></div>
            </div>
        </div>
        <!-- BUDGETS -->
        <div class="panel panel-default">
            <div class="panel-heading">
                <i class="fa fa-tasks fa-fw"></i> <a href="{{route('budgets.index')}}">Budgets and spending</a>
            </div>
            <div class="panel-body">
                <div id="budgets-chart"></div>
            </div>
        </div>
        <!-- CATEGORIES -->
        <div class="panel panel-default">
            <div class="panel-heading">
                <i class="fa fa-bar-chart fa-fw"></i> <a href="{{route('categories.index')}}">Categories</a>
            </div>
            <div class="panel-body">
                <div id="categories-chart"></div>
            </div>
        </div>
                <div class="panel panel-default">
                    <div class="panel-heading">
                        <i class="fa fa-line-chart"></i> Savings
                    </div>
                    <div class="panel-body">
                        (todo)
                    </div>
                </div>



    </div>
    <div class="col-lg-4 col-md-6 col-sm-12">

        <!-- REMINDERS -->
        <div class="panel panel-default">
            <div class="panel-heading">
                <i class="fa fa-calendar-o"></i> Bills
            </div>
            <div class="panel-body">
                <div id="bills-chart"></div>
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
                @include('list.journals-tiny',['transactions' => $data[0],'account' => $data[1]])
            </div>
        </div>
        @endforeach
    </div>
</div>

@endif


@stop
@section('scripts')
<script type="text/javascript">
    var currencyCode = '{{Amount::getCurrencyCode()}}';
</script>
<!-- load the libraries and scripts necessary for Google Charts: -->
<script type="text/javascript" src="https://www.google.com/jsapi"></script>
<script type="text/javascript" src="js/gcharts.options.js"></script>
<script type="text/javascript" src="js/gcharts.js"></script>



        <script type="text/javascript" src="js/index.js"></script>
@stop
@section('styles')
@stop
