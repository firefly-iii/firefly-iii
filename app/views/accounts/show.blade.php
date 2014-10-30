@extends('layouts.default')
@section('content')
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

        <!-- summary of the selected period -->
        <div class="panel panel-default">
                    <div class="panel-heading">
                        <i class="fa fa-fw fa-align-justify"></i> Summary
                    </div>
                    <div class="panel-body">
                    On the todo list.
                    {{--
                        <table class="table table-striped table-condensed">
                            <tr>
                                <th></th>
                                <th>Expense / income</th>
                                <th>Transfers</th>
                            </tr>
                            <tr>
                                <td>Out</td>
                                <td>
                                    {{mf($show['statistics']['period']['out'])}}
                                    <a href="{{route('accounts.show',$account->id)}}?type=transactions&amp;show=expenses"><span class="glyphicon glyphicon-circle-arrow-right"></span></a>
                                </td>
                                <td>
                                    {{mf($show['statistics']['period']['t_out'])}}
                                    <a href="{{route('accounts.show',$account->id)}}?type=transfers&amp;show=out"><span class="glyphicon glyphicon-circle-arrow-right"></span></a>
                                </td>
                            </tr>
                            <tr>
                                <td>In</td>
                                <td>
                                    {{mf($show['statistics']['period']['in'])}}
                                    <a href="{{route('accounts.show',$account->id)}}?type=transactions&amp;show=income"><span class="glyphicon glyphicon-circle-arrow-right"></span></a>
                                </td>
                                <td>
                                    {{mf($show['statistics']['period']['t_in'])}}
                                    <a href="{{route('accounts.show',$account->id)}}?type=transfers&amp;show=in"><span class="glyphicon glyphicon-circle-arrow-right"></span></a>
                                </td>
                            </tr>
                            <tr>
                                <td>Difference</td>
                                <td>{{mf($show['statistics']['period']['diff'])}}</td>
                                <td>{{mf($show['statistics']['period']['t_diff'])}}</td>
                            </tr>
                        </table>
                        --}}
                    </div>
                </div>
    </div>
</div>


<div class="row">
    <div class="col-lg-6 col-md-12 col-sm-12">
        <div class="panel panel-default">
            <div class="panel-heading">
                Out
            </div>
            <div class="panel-body">
                <div id="account-out-sankey"></div>
            </div>
        </div>
    </div>
    <div class="col-lg-6 col-md-12 col-sm-12">
        <div class="panel panel-default">
            <div class="panel-heading">
                In
            </div>
            <div class="panel-body">
                <div id="account-in-sankey"></div>
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
                <div id="account-transactions"></div>
            </div>
    </div>
</div>



{{--
<div class="row">
    <div class="col-lg-12 col-md-12 col-sm-12">
        <h4>Transactions <small> For selected account and period</small></h4>
        @include('paginated.transactions',['journals' => $show['journals'],'sum' => true])
    </div>
</div>
--}}
@stop

@section('styles')
@stop

@section('scripts')
<script type="text/javascript">
    var accountID = {{{$account->id}}};
</script>
<!-- load the libraries and scripts necessary for Google Charts: -->
<script type="text/javascript" src="https://www.google.com/jsapi"></script>
{{HTML::script('assets/javascript/firefly/gcharts.options.js')}}
{{HTML::script('assets/javascript/firefly/gcharts.js')}}



{{HTML::script('assets/javascript/firefly/accounts.js')}}
@stop