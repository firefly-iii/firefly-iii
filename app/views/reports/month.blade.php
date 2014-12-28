@extends('layouts.default')
@section('content')
{{ Breadcrumbs::renderIfExists(Route::getCurrentRoute()->getName(), $date) }}
<div class="row">
    <div class="col-lg-5 col-md-5 col-sm-12">
        <div class="panel panel-default">
            <div class="panel-heading">Income</div>
            @include('list.journals-small',['journals' => $income])
        </div>
    </div>
    <div class="col-lg-4 col-md-4 col-sm-12">
        <div class="panel panel-default">
            <div class="panel-heading">Expenses (top 10)</div>
            <table class="table table-bordered">
                <?php $sum = 0;?>
                @foreach($expenses as $id => $expense)
                    <?php $sum += floatval($expense['amount']);?>
                    <tr>
                        @if($id > 0)
                        <td><a href="{{route('accounts.show',$id)}}">{{{$expense['name']}}}</a></td>
                        @else
                        <td><em>{{{$expense['name']}}}</em></td>
                        @endif
                        <td>{{mf($expense['amount'])}}</td>
                    </tr>
                @endforeach
                <tr>
                    <td><em>Sum</em></td>
                    <td>{{mf($sum)}}</td>
                </tr>
            </table>
        </div>
    </div>
    <div class="col-lg-3 col-md-4 col-sm-12">
        <div class="panel panel-default">
            <div class="panel-heading">Sums</div>
            <?php
                $in = 0;
                foreach($income as $entry) {
                    $in += floatval($entry->transactions[1]->amount);
                }
            ?>
            <table class="table table-bordered">
                <tr>
                    <td>In</td>
                    <td>{{mf($in)}}</td>
                </tr>
                <tr>
                    <td>Out</td>
                    <td>{{mf($sum)}}</td>
                </tr>
                <tr>
                    <td>Difference</td>
                    <td>{{mf($in - $sum)}}</td>
                </tr>
            </table>
        </div>
    </div>
</div>
<div class="row">
    <div class="col-lg-6 col-md-6 col-sm-12">
        <div class="panel panel-default">
            <div class="panel-heading">Budgets</div>
                <table class="table table-bordered">
                    <tr>
                        <th>Budget</th>
                        <th>Envelope</th>
                        <th>Spent</th>
                        <th>Left</th>
                    </tr>
                    <?php
                        $sumSpent = 0;
                        $sumEnvelope = 0;
                        $sumLeft = 0;
                    ?>
                    @foreach($budgets as $id => $budget)
                        <?php
                            $sumSpent += $budget['spent'];
                            $sumEnvelope += $budget['amount'];
                            $sumLeft += $budget['amount'] + $budget['spent'];
                        ?>
                    <tr>
                        <td>
                            @if($id > 0)
                                <a href="{{route('budgets.show',$id)}}">{{{$budget['name']}}}</a>
                            @else
                                <em>{{{$budget['name']}}}</em>
                            @endif
                        </td>
                        <td>{{mf($budget['amount'])}}</td>
                        <td>{{mf($budget['spent'],false)}}</td>
                        <td>{{mf($budget['amount'] + $budget['spent'])}}</td>
                    </tr>
                    @endforeach
                    <tr>
                        <td><em>Sum</em></td>
                        <td>{{mf($sumEnvelope)}}</td>
                        <td>{{mf($sumSpent)}}</td>
                        <td>{{mf($sumLeft)}}</td>
                    </tr>
                </table>
        </div>
    </div>
    <div class="col-lg-6 col-md-6 col-sm-12">
        <div class="panel panel-default">
            <div class="panel-heading">Categories</div>
            <table class="table table-bordered">
                <tr>
                    <th>Category</th>
                    <th>Spent</th>
                </tr>
                <?php $sum = 0;?>
                @foreach($categories as $id => $category)
                    <?php $sum += floatval($category['amount']);?>
                    <tr>
                        <td>
                            @if($id > 0)
                                <a href="{{route('categories.show',$id)}}">{{{$category['name']}}}</a>
                            @else
                                <em>{{{$category['name']}}}</em>
                            @endif
                        </td>
                        <td>{{mf($category['amount'],false)}}</td>
                    </tr>
                @endforeach
                <tr>
                    <td><em>Sum</em></td>
                    <td>{{mf($sum)}}</td>
                </tr>
            </table>
        </div>
    </div>
</div>
<div class="row">
    <div class="col-lg-12 col-md-12 col-sm-12">
        <div class="panel panel-default">
            <div class="panel-heading">Accounts</div>
            <table class="table table-bordered">
                <?php
                    $sumStart = 0;
                    $sumEnd = 0;
                    $sumDiff = 0;
                ?>
            @foreach($accounts as $id => $account)
                <?php
                    $sumStart += $account['startBalance'];
                    $sumEnd += $account['endBalance'];
                    $sumDiff += $account['difference'];
                ?>
                <tr>
                    <td><a href="{{route('accounts.show',$id)}}">{{{$account['name']}}}</a></td>
                    <td>{{mf($account['startBalance'])}}</td>
                    <td>{{mf($account['endBalance'])}}</td>
                    <td>{{mf($account['difference'])}}</td>
                </tr>
            @endforeach
                <tr>
                    <td><em>Sum</em></td>
                    <td>{{mf($sumStart)}}</td>
                    <td>{{mf($sumEnd)}}</td>
                    <td>{{mf($sumDiff)}}</td>
                </tr>
            </table>
        </div>
    </div>
</div>
<div class="row">
    <div class="col-lg-6 col-md-6 col-sm-12">
        <div class="panel panel-default">
            <div class="panel-heading">Piggy banks</div>
            <div class="panel-body">Body</div>
        </div>
    </div>
    <div class="col-lg-6 col-md-6 col-sm-12">
        <div class="panel panel-default">
            <div class="panel-heading">Repeated expenses</div>
            <div class="panel-body">Body</div>
        </div>
    </div>
</div>
<div class="row">
    <div class="col-lg-12 col-md-12 col-sm-12">
        <div class="panel panel-default">
            <div class="panel-heading">Recurring transactions</div>
            <div class="panel-body">Body</div>
        </div>
    </div>
</div>
<div class="row">
    <div class="col-lg-12 col-md-12 col-sm-12">
        <div class="panel panel-default">
            <div class="panel-heading">Outside of budgets</div>
            <div class="panel-body">Body</div>
        </div>
    </div>
</div>
@stop