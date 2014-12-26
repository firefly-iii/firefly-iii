@extends('layouts.default')
@section('content')
{{ Breadcrumbs::renderIfExists(Route::getCurrentRoute()->getName()) }}
<div class="row">
    <div class="col-lg-6 col-md-6 col-sm-12">
        <div class="panel panel-default">
            <div class="panel-heading">Income</div>
            @include('list.journals-small',['journals' => $income])
        </div>
    </div>
    <div class="col-lg-6 col-md-6 col-sm-12">
        <div class="panel panel-default">
            <div class="panel-heading">Expenses (top 10)</div>
            <table class="table table-bordered">
                <?php $sum = 0;?>
                @foreach($expenses as $expense)
                    <?php $sum += floatval($expense->sum);?>
                    <tr>
                        @if($expense->account_id != 0)
                        <td><a href="{{route('accounts.show',$expense->account_id)}}">{{{$expense->name}}}</a></td>
                        @else
                        <td><em>{{{$expense->name}}}</em></td>
                        @endif
                        <td>{{mf($expense->sum)}}</td>
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
                    <?php $sum = 0;?>
                    @foreach($budgets as $budget)
                        <?php $sum += floatval($budget->spent);?>
                    <tr>
                        <td>
                            @if($budget->id > 0)
                                <a href="{{route('budgets.show',$budget->id)}}">{{{$budget->name}}}</a>
                            @else
                                <em>{{{$budget->name}}}</em>
                            @endif
                        </td>
                        <td>{{mf($budget->budget_amount)}}</td>
                        <td>{{mf($budget->spent,false)}}</td>
                        <td>{{mf(floatval($budget->budget_amount) - floatval($budget->spent))}}</td>
                    </tr>
                    @endforeach
                    <tr>
                        <td colspan="2"><em>Sum</em></td>
                        <td colspan="2">{{mf($sum)}}</td>
                    </tr>
                </table>
            <div class="panel-body">
                <em>This list does not take in account outgoing transfers to shared accounts.</em>
            </div>
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
                @foreach($categories as $category)
                    <?php $sum += floatval($category->sum);?>
                    <tr>
                        <td>
                            @if($category->id > 0)
                                <a href="{{route('categories.show',$category->id)}}">{{{$category->name}}}</a>
                            @else
                                <em>{{{$category->name}}}</em>
                            @endif
                        </td>
                        <td>{{mf($category->sum,false)}}</td>
                    </tr>
                @endforeach
                <tr>
                    <td><em>Sum</em></td>
                    <td>{{mf($sum)}}</td>
                </tr>
            </table>
            <div class="panel-body">
                <em>This list does not take in account outgoing transfers to shared accounts.</em>
            </div>
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
            @foreach($accounts as $account)
                <?php
                    $sumStart += $account->startBalance;
                    $sumEnd += $account->endBalance;
                    $sumDiff += $account->difference;
                ?>
                <tr>
                    <td><a href="#">{{{$account->name}}}</a></td>
                    <td>{{mf($account->startBalance)}}</td>
                    <td>{{mf($account->endBalance)}}</td>
                    <td>{{mf($account->difference)}}</td>
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