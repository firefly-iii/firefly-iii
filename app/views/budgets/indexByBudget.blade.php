@extends('layouts.default')
@section('content')
<div class="row">
    <div class="col-lg-12 col-md-12 col-sm-12">
        <h1>Firefly
            <small>Budgets and envelopes</small>
        </h1>
        <p class="lead">Use budgets to organize and limit your expenses.</p>

        <p class="text-info">
            Budgets are groups of expenses that reappear every [period]*. Examples could be "groceries", "bills" or
            "drinks with friends". The table below lists all of the budgets you have, if any.
            <a href="http://dictionary.reference.com/browse/budget">By definition</a>, budgets are an estimated amount
            of money, ie. € 400,-. Such an estimation can change over time but <em>should</em> always be set. Budgets
            without an actual budget are fairly pointless.
        </p>
        <p class="text-info">
            Use this page to create or change budgets and the estimated amount of money you think is wise. Pages further ahead
            will explain what an "envelope" is in the context of budgeting.
        </p>
        <p class="text-info">
            * <small>Every month, week, year, etc.</small>
        </p>

        <p>
            <div class="btn-group">
            <a href="{{route('budgets.create')}}" class="btn btn-success"><span class="glyphicon glyphicon-plus"></span> Create a new budget</a>
            <a href="{{route('budgets.limits.create')}}" class="btn btn-success"><span class="glyphicon glyphicon-plus"></span> Create a new envelope</a>
            </div>
        </p>

        <div class="btn-group">
            <a class="btn btn-default" href ="{{route('budgets.index')}}"><span class="glyphicon glyphicon-indent-left"></span> Group by date</a>
            <a class="btn btn-default" href ="{{route('budgets.create')}}?from=budget"><span class="glyphicon glyphicon-plus-sign"></span> Create a new budget</a>
            <a class="btn btn-default" href ="{{route('budgets.limits.create')}}?from=budget"><span class="glyphicon glyphicon-plus-sign"></span> Create a new envelope</a>
        </div>
        </p>
    </div>
</div>
<div class="row">
    <div class="col-lg-12 col-md-12 col-sm-12">
        <table class="table table-bordered table-striped">
            <tr>
                <th>Budget</th>
                <th>Current envelope(s)</th>
                <th>Update budget</th>
            </tr>
            @foreach($budgets as $budget)
            <tr>
                <td>
                    <a href="{{route('budgets.show',$budget->id)}}">{{{$budget->name}}}</a>

                </td>
                <td>
                    <div class="row">
                        <div class="col-sm-2">
                            <small>Envelope</small>
                        </div>
                        <div class="col-sm-2">
                            <small>Left</small>
                        </div>
                    </div>
                    @foreach($budget->limits as $limit)
                    @foreach($limit->limitrepetitions as  $index => $rep)
                    <div class="row">
                        <div class="col-sm-2">
                            <span class="label label-primary">
                                <span class="glyphicon glyphicon-envelope"></span>
                                {{mf($rep->amount,false)}}</span>
                        </div>
                        <div class="col-sm-2">
                            @if($rep->left < 0)
                            <span class="label label-danger">
                                <span class="glyphicon glyphicon-envelope"></span>
                                {{mf($rep->left,false)}}</span>
                            @else
                            <span class="label label-success">
                                <span class="glyphicon glyphicon-envelope"></span>
                                {{mf($rep->left,false)}}</span>
                            @endif
                        </div>
                        <div class="col-sm-3">
                            <small>
                                <a href="{{route('budgets.show',$budget->id,$rep->id)}}">
                                {{$rep->periodShow()}}
                                </a>
                            </small>
                        </div>
                        @if($limit->repeats == 1)
                        <div class="col-sm-2">
                            <span class="label label-warning">auto repeats</span>
                        </div>
                        @endif
                        <div class="col-sm-2 @if($limit->repeats == 0) col-sm-offset-2 @endif">
                            <div class="btn-group btn-group-xs">
                                <a href="{{route('budgets.limits.edit',$limit->id)}}?from=budget" class="btn btn-xs btn-default"><span class="glyphicon glyphicon-pencil"></span></a>
                                @if($limit->repeats == 0 || ($limit->repeats == 1 && $index == 0))
                                <a href="{{route('budgets.limits.delete',$limit->id)}}?from=budget" class="btn btn-xs btn-danger"><span class="glyphicon glyphicon-trash"></span></a>
                                @endif
                            </div>
                        </div>
                    </div>
                    @endforeach
                    @endforeach
                    <p style="margin-top:5px;">
                        <a href="{{route('budgets.limits.create',$budget->id)}}?from=budget" class="btn btn-default btn-xs"><span
                                class="glyphicon-plus-sign glyphicon"></span> Add another envelope</a>
                    </p>
                </td>
                <td>
                    <div class="btn-group btn-group-sm">
                        <a href="{{route('budgets.edit',$budget->id)}}?from=budget" class="btn btn-default"><span class="glyphicon glyphicon-pencil"></span></a>
                        <a href="{{route('budgets.delete',$budget->id)}}?from=budget" class="btn btn-danger"><span class="glyphicon glyphicon-trash"></span></a>

                    </div>
                </td>
            </tr>
            @endforeach

        </table>

    </div>
</div>
@stop