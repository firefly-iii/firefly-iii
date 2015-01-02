@extends('layouts.default')
@section('content')
{{ Breadcrumbs::renderIfExists(Route::getCurrentRoute()->getName(), $date) }}
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
    <div class="col-lg-6 col-md-6 col-sm-6">
        <div class="panel panel-default">
            <div class="panel-heading">
                Account balance
            </div>
            <table class="table table-bordered table-striped">
            <?php
            $start = 0;
            $end   = 0;
            $diff  = 0;
            ?>
                @foreach($balances as $balance)
                <?php
                $start += $balance['start'];
                $end   += $balance['end'];
                $diff  += ($balance['end']-$balance['start']);
                ?>
                <tr>
                    <td>
                        <a href="{{route('accounts.show',$balance['account']->id)}}">{{{$balance['account']->name}}}</a>
                        @if($balance['shared'])
                        <small><em>shared</em></small>
                        @endif
                    </td>
                    <td>{{mf($balance['start'])}}</td>
                    <td>{{mf($balance['end'])}}</td>
                    <td>{{mf($balance['end']-$balance['start'])}}</td>
                </tr>
                @endforeach
                <tr>
                    <td><em>Sum of sums</em></td>
                    <td>{{mf($start)}}</td>
                    <td>{{mf($end)}}</td>
                    <td>{{mf($diff)}}</td>
                </tr>
            </table>
        </div>

        <div class="panel panel-default">
            <div class="panel-heading">
                Income vs. expense
            </div>
            <?php
            $incomeSum = 0;
            $expenseSum = 0;
            foreach($groupedIncomes as $income) {
                $incomeSum += floatval($income->sum);
            }
            foreach($groupedExpenses as $exp) {
                $expenseSum += floatval($exp['amount']);
            }
            $incomeSum = floatval($incomeSum*-1);

            ?>

                <table class="table table-bordered table-striped">
                    <tr>
                        <td>In</td>
                        <td>{{mf($incomeSum)}}</td>
                    </tr>
                    <tr>
                        <td>Out</td>
                        <td>{{mf($expenseSum*-1)}}</td>
                    </tr>
                    <tr>
                        <td>Difference</td>
                        <td>{{mf($incomeSum - $expenseSum)}}</td>
                    </tr>
                </table>
        </div>
    </div>
    <div class="col-lg-3 col-md-3 col-sm-3">
        <div class="panel panel-default">
            <div class="panel-heading">
                Income
            </div>
            <table class="table">
                <?php $sum = 0;?>
            @foreach($groupedIncomes as $income)
                <?php $sum += floatval($income->sum)*-1;?>
            <tr>
                <td><a href="{{route('accounts.show',$income->account_id)}}">{{{$income->name}}}</a></td>
                <td>{{mf(floatval($income->sum)*-1)}}</td>
            </tr>
            @endforeach
                <tr>
                    <td><em>Sum</em></td>
                    <td>{{mf($sum)}}</td>
                </tr>
            </table>
        </div>
    </div>
    <div class="col-lg-3 col-md-3 col-sm-3">
        <div class="panel panel-default">
            <div class="panel-heading">
                Expenses
            </div>
            <table class="table">
                @foreach($groupedExpenses as $id => $expense)
                <tr>
                    <td><a href="{{route('accounts.show',$id)}}">{{{$expense['name']}}}</a></td>
                    <td>{{mf(floatval($expense['amount'])*-1)}}</td>
                </tr>
                @endforeach
            </table>
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
                <!-- <div id="budgets"></div> -->
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
var currencyCode = '{{getCurrencyCode()}}';
</script>

{{HTML::script('assets/javascript/firefly/reports.js')}}

@stop
