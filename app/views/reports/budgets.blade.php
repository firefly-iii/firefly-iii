@extends('layouts.default')
@section('content')
<div class="row">
    <div class="col-lg-12 col-md-12 col-sm-12">
        <h3>Budgets</h3>
    </div>
</div>
<div class="row">
    @foreach($budgets as $budget)
    <div class="col-lg-3 col-md-4 col-sm-6">
        <div class="panel panel-default">
            <div class="panel-heading">
            {{{$budget->name}}}
            </div>
            <div class="panel-body">
            @foreach($budget->repInfo as $repetition)
                <p class="text-center">{{{$repetition['date']}}}</p>
                <div class="progress progress-striped">
                    @if($repetition['overspent'])
                    <div class="progress-bar progress-bar-warning" role="progressbar" aria-valuenow="{{$repetition['pct']}}" aria-valuemin="0" aria-valuemax="100" style="width: {{$repetition['pct']}}%;min-width:15px;">
                        {{$repetition['pct_display']}}%
                    </div>
                    <div class="progress-bar progress-bar-danger" role="progressbar" aria-valuenow="{{100-$repetition['pct']}}" aria-valuemin="0" aria-valuemax="100" style="width: {{100-$repetition['pct']}}%;">
                    </div>
                    @else
                    <div class="progress-bar" role="progressbar" aria-valuenow="{{$repetition['pct']}}" aria-valuemin="0" aria-valuemax="100" style="width: {{$repetition['pct']}}%;min-width:15px;">
                        {{$repetition['pct_display']}}%
                    </div>
                    @endif
                </div>
                <table class="table">
                    <tr>
                        <td style="width:50%">Budgeted</td>
                        <td>{{mf($repetition['budgeted'])}}</td>
                    </tr>
                    <tr>
                        <td>Spent</td>
                        <td>{{mf($repetition['spent'])}}</td>
                    </tr>
                    <tr>
                        <td>Left</td>
                        <td>{{mf($repetition['left'])}}</td>
                    </tr>

                </table>
            @endforeach
            <!--

                Progressbar, Spent, budgeted, left
            </div>
            -->
        </div>
    </div>
    </div>
    @endforeach
</div>
<div class="row">
    <div class="col-lg-12 col-md-12 col-sm-12">
        <h3>Accounts</h3>
    </div>
</div>
<div class="row">
@foreach($accounts as $account)
    <div class="col-lg-4 col-md-6 col-sm-12">
        <div class="panel panel-default">
            <div class="panel-heading">
            {{{$account->name}}}
            </div>
            <div class="panel-body">

                List of budgets with: expenses, left in budget
                Balance at start of month.

                Balance at end of month + left in all (relevant budgets)
            </div>
            <table class="table">
            <?php $sum = 0;?>
            @foreach($account->budgetInfo as $budget)
            <?php $sum += $budget['spent'];?>
            <tr>
                <td>{{$budget['budget_name']}}</td>
                <td>{{mf($budget['budgeted'])}}</td>
                <td>{{mf($budget['spent'])}}</td>
                <td>{{mf($budget['left'])}}</td>

            </tr>
            @endforeach
            @if($sum != 0)
            <tr>
                <td><em>Sum</em></td>
                <td></td>
                <td>{{mf($sum)}}</td>
                <td></td>
            </tr>
            @endif
            </table>
        </div>
    </div>
@endforeach
</div>

@stop
@section('scripts')
@stop