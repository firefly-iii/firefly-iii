@extends('layouts.default')
@section('content')
<div class="row">
 <div class="col-lg-10 col-md-8 col-sm-12">
    <div class="panel panel-default">
        <div class="panel-heading">
            Income vs. expenses
        </div>
        <div class="panel-body">
            <div id="income-expenses-chart"></div>
        </div>
    </div>
 </div>
 <div class="col-lg-2 col-md-4 col-sm-12">
    <div class="panel panel-default">
        <div class="panel-heading">
            Income vs. expenses
        </div>
        <div class="panel-body">
            <div id="income-expenses-sum-chart"></div>
        </div>
    </div>
 </div>
</div>

<div class="row">
    <div class="col-lg-12 col-md-12 col-sm-12">
        <div class="panel panel-default">
            <div class="panel-heading">
                Summary
            </div>
            <div class="panel-body">
                <table class="table table-striped">
                    <tr>
                        <td></td>
                            @foreach($summary as $entry)
                                <th>{{$entry['month']}}</th>
                        @endforeach
                        <th>Sum</th>
                    </tr>
                    <tr>
                        <th>In</th>
                        <?php $inSum = 0;?>
                        @foreach($summary as $entry)
                            <td>{{mf($entry['income'])}}</td>
                            <?php $inSum+=$entry['income'];?>
                        @endforeach
                        <td>{{mf($inSum)}}</td>
                    </tr>
                        <th>Out</th>
                        <?php $outSum = 0;?>
                        @foreach($summary as $entry)
                            <td>{{mf($entry['expense']*-1)}}</td>
                            <?php $outSum+=$entry['expense']*-1;?>
                        @endforeach
                        <td>{{mf($outSum)}}</td>
                    <tr>
                        <th>Difference</th>
                        @foreach($summary as $entry)
                            <td>{{mf($entry['income']- $entry['expense'])}}</td>
                        @endforeach
                        <td>{{mf($inSum + $outSum)}}</td>
                    </tr>
                </table>
            </div>
        </div>
    </div>
</div>

<div class="row">
    <div class="col-lg-12 col-md-12 col-sm-12">
        <div class="panel panel-default">
            <div class="panel-heading">
                Budgets
            </div>
            <div class="panel-body">
                <div id="budgets"></div>
            </div>
        </div>
    </div>
</div>

@stop
@section('scripts')
<!-- load the libraries and scripts necessary for Google Charts: -->
<script type="text/javascript" src="https://www.google.com/jsapi"></script>
{{HTML::script('assets/javascript/firefly/gcharts.options.js')}}
{{HTML::script('assets/javascript/firefly/gcharts.js')}}

<script type="text/javascript">
var year = '{{$year}}';

</script>

{{HTML::script('assets/javascript/firefly/reports.js')}}

@stop