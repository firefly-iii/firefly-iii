@extends('layouts.default')
@section('content')
<div class="row">
    <div class="col-lg-8 col-md-6 col-sm-12">
        <div class="panel panel-default">
            <div class="panel-heading">
                <i class="fa fa-fw {{$subTitleIcon}} fa-fw"></i> {{{$account->name}}}
            </div>
            <div class="panel-body">
                <div id="overviewChart"></div>
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
                <div id="accountOutSankey"><img src="http://placehold.it/550x300" title="Placeholder" alt="" /></div>
            </div>
        </div>
    </div>
    <div class="col-lg-6 col-md-12 col-sm-12">
        <div class="panel panel-default">
            <div class="panel-heading">
                In
            </div>
            <div class="panel-body">
                <div id="accountInSankey"><img src="http://placehold.it/550x300" title="Placeholder" alt="" /></div>
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

            <table id="transactionByAccountTable" class="table table-striped table-bordered" >
            <thead>
                <tr>
                    <th>Date</th>
                    <th>Description</th>
                    <th>Amount (&euro;)</th>
                    <th>From</th>
                    <th>To</th>
                    <th>Budget / category</th>
                    <th>ID</th>
                </tr>
            </thead>
            </table>

            {{--
                <table class="table table-striped table-condensed">
                    @if(count($show['statistics']['accounts']) > 0)
                    <tr>
                        <td style="width:30%;">Related accounts</td>
                        <td>
                            @foreach($show['statistics']['accounts'] as $acct)
                            <a href="{{route('accounts.show',$acct->id)}}" class="btn btn-default btn-xs">{{{$acct->name}}}</a>
                            @endforeach
                        </td>
                    </tr>
                    @endif
                    @if(isset($show['statistics']['Category']) && count($show['statistics']['Category']) > 0)
                    <tr>
                        <td>Related categories</td>
                        <td>
                            @foreach($show['statistics']['Category'] as $cat)
                            <a href="{{route('categories.show',$cat->id)}}" class="btn btn-default btn-xs">{{{$cat->name}}}</a>
                            @endforeach
                        </td>
                    </tr>
                    @endif
                    @if(isset($show['statistics']['Budget']) && count($show['statistics']['Budget']) > 0)
                    <tr>
                        <td>Related budgets</td>
                        <td>
                            @foreach($show['statistics']['Budget'] as $bud)
                            <a href="{{route('budgets.show',$bud->id)}}?useSession=true" class="btn btn-default btn-xs">{{{$bud->name}}}</a>
                            @endforeach
                        </td>
                    </tr>
                    @endif
                </table>
                --}}
            </div>
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
{{HTML::style('assets/stylesheets/highslide/highslide.css')}}
{{HTML::style('assets/stylesheets/datatables/dataTables.bootstrap.css')}}
@stop

@section('scripts')
<script type="text/javascript">
    var accountID = {{{$account->id}}};
</script>
{{HTML::script('assets/javascript/datatables/jquery.dataTables.min.js')}}
{{HTML::script('assets/javascript/datatables/dataTables.bootstrap.js')}}
{{HTML::script('assets/javascript/highcharts/highcharts.js')}}
{{HTML::script('assets/javascript/firefly/accounts.js')}}
@stop