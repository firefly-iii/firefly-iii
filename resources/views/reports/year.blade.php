@extends('layouts.default')
@section('content')
{!! Breadcrumbs::renderIfExists(Route::getCurrentRoute()->getName(), $date) !!}
<div class="row">
    <div class="col-lg-12 col-md-12 col-sm-12 col-xs-12">
        <p>
            <a href="#" class="btn btn-default" id="includeShared" style="display:none;">
                <i class="state-icon glyphicon glyphicon-unchecked"></i>
                Include shared asset accounts</a>
        </p>
    </div>
</div>
<div class="row">
 <div class="col-lg-10 col-md-8 col-sm-12">
    <div class="panel panel-default">
        <div class="panel-heading">
            <i class="fa fa-line-chart"></i>
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
            <i class="fa fa-calendar"></i>
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
                <i class="fa fa-credit-card fa-fw"></i>
                Account balance
            </div>
            <table class="table table-bordered table-striped">
                <tr>
                    <th>Name</th>
                    <th>Balance at start of year</th>
                    <th>Balance at end of year</th>
                    <th>Difference</th>
                </tr>
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
                @if($balance['hide'] === false)
                    <tr>
                        <td>
                            <a href="{{route('accounts.show',$balance['account']->id)}}">{{{$balance['account']->name}}}</a>
                            @if($balance['shared'])
                            <small><em>shared</em></small>
                            @endif
                        </td>
                        <td>{!! Amount::format($balance['start']) !!}</td>
                        <td>{!! Amount::format($balance['end']) !!}</td>
                        <td>{!! Amount::format($balance['end']-$balance['start']) !!}</td>
                    </tr>
                    @endif
                @endforeach
                <tr>
                    <td><em>Sum of sums</em></td>
                    <td>{!! Amount::format($start) !!}</td>
                    <td>{!! Amount::format($end) !!}</td>
                    <td>{!! Amount::format($diff) !!}</td>
                </tr>
            </table>
        </div>

        <div class="panel panel-default">
            <div class="panel-heading">
                <i class="fa fa-fw fa-exchange" title="Transfer"></i>
                Income vs. expense
            </div>
            <?php
            $incomeSum = 0;
            $expenseSum = 0;
            foreach($groupedIncomes as $income) {
                $incomeSum += floatval($income->queryAmount);
            }
            foreach($groupedExpenses as $exp) {
                $expenseSum += floatval($exp['queryAmount']);
            }
            $incomeSum = floatval($incomeSum*-1);

            ?>

                <table class="table table-bordered table-striped">
                    <tr>
                        <td>In</td>
                        <td>{!! Amount::format($incomeSum) !!}</td>
                    </tr>
                    <tr>
                        <td>Out</td>
                        <td>{!! Amount::format($expenseSum*-1) !!}</td>
                    </tr>
                    <tr>
                        <td>Difference</td>
                        <td>{!! Amount::format($incomeSum - $expenseSum) !!}</td>
                    </tr>
                </table>
        </div>
    </div>
    <div class="col-lg-3 col-md-3 col-sm-3">
        <div class="panel panel-default">
            <div class="panel-heading">
                <i class="fa fa-long-arrow-right fa-fw"></i>
                Income
            </div>
            <table class="table">
                <?php $sum = 0;?>
            @foreach($groupedIncomes as $income)
                <?php
                $sum += floatval($income->queryAmount)*-1;
                ?>
            <tr>
                <td><a href="{{route('accounts.show',$income->account_id)}}">{{{$income->name}}}</a></td>
                <td>{!! Amount::format(floatval($income->queryAmount)*-1) !!}</td>
            </tr>
            @endforeach
                <tr>
                    <td><em>Sum</em></td>
                    <td>{!! Amount::format($sum) !!}</td>
                </tr>
            </table>
        </div>
    </div>
    <div class="col-lg-3 col-md-3 col-sm-3">
        <div class="panel panel-default">
            <div class="panel-heading">
                <i class="fa fa-long-arrow-left fa-fw"></i>
                Expenses
            </div>
            <table class="table">
                <?php $sum = 0;?>
                @foreach($groupedExpenses as $expense)
                <tr>
                    <td><a href="{{route('accounts.show',$expense['id'])}}">{{{$expense['name']}}}</a></td>
                    <td>{!! Amount::format(floatval($expense['queryAmount'])*-1) !!}</td>
                </tr>
                <?php $sum += floatval($expense['queryAmount'])*-1;?>
                @endforeach
                <tr>
                    <td><em>Sum</em></td>
                    <td>{!! Amount::format($sum) !!}</td>
                </tr>
            </table>
        </div>
    </div>
</div>
<div class="row">
    <div class="col-lg-12 col-md-12 col-sm-12">
        <div class="panel panel-default">
            <div class="panel-heading">
                <i class="fa fa-tasks fa-fw"></i>
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
<script type="text/javascript" src="js/gcharts.options.js"></script>
<script type="text/javascript" src="js/gcharts.js"></script>

<script type="text/javascript">
var year = '{{$year}}';
var currencyCode = '{{Amount::getCurrencyCode()}}';
</script>

<script type="text/javascript" src="js/reports.js"></script>

@stop
