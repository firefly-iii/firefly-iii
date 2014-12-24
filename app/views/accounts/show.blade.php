@extends('layouts.default')
@section('content')
{{ Breadcrumbs::renderIfExists(Route::getCurrentRoute()->getName(), $account) }}
<div class="row">
    <div class="col-lg-8 col-md-6 col-sm-12">
        <div class="panel panel-default">
            <div class="panel-heading">
                <i class="fa fa-fw {{$subTitleIcon}} fa-fw"></i> {{{$account->name}}}
            </div>
            <div class="panel-body">
                <div id="overview-chart"></div>
            </div>
        </div>
    </div>
    <div class="col-lg-4 col-md-6 col-sm-12">
        <!-- time based navigation -->
        @include('partials.date_nav')
        <div class="panel panel-default">
            <div class="panel-heading">
                <i class="fa fa-clock-o fa-fw"></i> View options for {{{$account->name}}}
            </div>
            <div class="panel-body">
                <p>
                    @if($range == 'all')
                        <a href="{{route('accounts.show',$account->id)}}/session" class="btn btn-default">Stick to date-range</a>
                    @else
                        <a href="{{route('accounts.show',$account->id)}}/all" class="btn btn-default">Show all transactions</a>
                    @endif
                </p>
            </div>
        </div>
    </div>
</div>


<div class="row">
    <div class="col-lg-12 col-md-12 col-sm-12">
        <div class="panel panel-default">
            <div class="panel-heading">
                <i class="fa fa-repeat fa-fw"></i> Transactions
            </div>
            <div class="panel-body">
                @include('list.journals-full')
            </div>
    </div>
</div>



@stop
@section('scripts')
<script type="text/javascript">
    var accountID = {{{$account->id}}};
    var view = '{{{$range}}}';
    var currencyCode = '{{getCurrencyCode()}}';
</script>
<!-- load the libraries and scripts necessary for Google Charts: -->
<script type="text/javascript" src="https://www.google.com/jsapi"></script>
{{HTML::script('assets/javascript/firefly/gcharts.options.js')}}
{{HTML::script('assets/javascript/firefly/gcharts.js')}}
{{HTML::script('assets/javascript/firefly/accounts.js')}}
@stop