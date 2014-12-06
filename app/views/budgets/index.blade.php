@extends('layouts.default')
@section('content')
{{ Breadcrumbs::renderIfExists(Route::getCurrentRoute()->getName()) }}
<div class="row">
    <div class="col-lg-9 col-sm-8 col-md-8">
        <div class="panel panel-default">
            <div class="panel-heading">
                {{\Session::get('start')->format('F Y')}}
            </div>
            <div class="panel-body">
                <div class="row">
                    <div class="col-lg-6 col-md-4 col-sm-3">
                        <small>Budgeted: <span id="budgetedAmount" data-value="300">{{mf(300)}}</span></small>
                    </div>
                    <div class="col-lg-6 col-md-4 col-sm-3" style="text-align:right;">
                        <small>Income {{\Session::get('start')->format('F Y')}}:
                        <a href="#" class="updateIncome"><span id="totalAmount" data-value="{{$budgetAmount->data}}">{{mf($budgetAmount->data)}}</span></a></small>
                    </div>
                </div>

                <div class="row">
                    <div class="col-lg-12 col-md-12 col-sm-12">
                        <div class="progress progress-striped">
                            <div class="progress-bar progress-bar-info" id="progress-bar-default" role="progressbar" aria-valuenow="0" aria-valuemin="0" aria-valuemax="100" style="width: 0;"></div>
                            <div class="progress-bar progress-bar-danger" id="progress-bar-danger" role="progressbar" aria-valuenow="0" aria-valuemin="0" aria-valuemax="100" style="width: 0;"></div>
                            <div class="progress-bar progress-bar-warning" id="progress-bar-warning" role="progressbar" aria-valuenow="10" aria-valuemin="0" aria-valuemax="100" style="width: 0;"></div>
                        </div>
                    </div>
                </div>
                <div class="row">
                    <div class="col-lg-6 col-md-4 col-sm-3">
                        <small>Spent: {{mf($spent)}}</small>
                    </div>
                </div>
                <div class="row">
                    <div class="col-lg-12 col-md-12 col-sm-12">
                        <div class="progress progress-striped">
                            @if($overspent)
                                <div class="progress-bar progress-bar-warning" role="progressbar" aria-valuenow="{{$spentPCT}}" aria-valuemin="0" aria-valuemax="100" style="width: {{$spentPCT}}%;"></div>
                                <div class="progress-bar progress-bar-danger" role="progressbar" aria-valuenow="{{100-$spentPCT}}" aria-valuemin="0" aria-valuemax="100" style="width: {{100-$spentPCT}}%;"></div>
                            @else
                                <div class="progress-bar progress-bar-info" role="progressbar" aria-valuenow="{{$spentPCT}}" aria-valuemin="0" aria-valuemax="100" style="width: {{$spentPCT}}%;"></div>
                            @endif
                        </div>
                    </div>
                </div>

            </div>
        </div>
    </div>
    <div class="col-lg-3 col-sm-4 col-md-4">
        <!-- time based navigation -->
        @include('partials.date_nav')
    </div>
</div>

<div class="row">
@foreach($budgets as $budget)
    <div class="col-lg-3 col-sm-4 col-md-6" style="height:180px;">
        <div class="panel panel-default">
            <div class="panel-heading">
                @if($budget->currentRep)
                    <a href="{{route('budgets.show',[$budget->id,$budget->currentRep->id])}}" id="budget-link-{{$budget->id}}">{{{$budget->name}}}</a>
                @else
                    <a href="{{route('budgets.show',$budget->id)}}" id="budget-link-{{$budget->id}}">{{{$budget->name}}}</a>
                @endif


                <!-- ACTIONS MENU -->
                <div class="pull-right">
                    <div class="btn-group">
                        <button type="button" class="btn btn-default btn-xs dropdown-toggle" data-toggle="dropdown">
                            Actions
                            <span class="caret"></span>
                        </button>
                        <ul class="dropdown-menu pull-right" role="menu">
                            <li><a href="{{route('budgets.edit',$budget->id)}}"><i class="fa fa-pencil fa-fw"></i> Edit</a></li>
                            <li><a href="{{route('budgets.delete',$budget->id)}}"><i class="fa fa-trash fa-fw"></i> Delete</a></li>
                        </ul>
                    </div>
                </div>

            </div>
            <div class="panel-body">
            <!-- the range in which the budget can be set -->
            <p>
                @if($budget->currentRep)
                    <input type="range" data-id="{{$budget->id}}" data-spent="{{$budget->spent}}" id="budget-range-{{$budget->id}}" max="900" min="0" value="{{$budget->currentRep->amount}}" />
                @else
                    <input type="range" data-id="{{$budget->id}}" data-spent="{{$budget->spent}}" id="budget-range-{{$budget->id}}" max="900" min="0" value="0" />
                @endif
            </p>
            <!-- some textual info about the budget. Updates dynamically. -->
            <p>
            <!-- budget-holder-X holds all the elements -->
            <span id="budget-holder-{{$budget->id}}">
            @if($budget->currentRep)
                <!-- budget-description-X holds the description. -->
                <span id="budget-description-{{$budget->id}}">Budgeted: </span>
                <!-- budget-info-X holds the input and the euro-sign: -->
                <span id="budget-info-{{$budget->id}}">
                @if($budget->limit > $budget->spent)
                    <span class="text-success">&euro;</span> <input type="number" min="0" max="900" data-id="{{$budget->id}}" step="1" value="{{$budget->limit}}" style="width:50px;color:#3c763d;" />
                @else
                    <span class="text-danger">&euro;</span> <input type="number" min="0" max="900"  data-id="{{$budget->id}}" step="1" value="{{$budget->limit}}" style="width:50px;color:#a94442;" />
                @endif
                </span>
            @else
                <span id="budget-description-{{$budget->id}}"><em>No budget</em></span>
                <span id="budget-info-{{$budget->id}}">
                    <span class="text-success" style="display:none;">&euro;</span> <input data-id="{{$budget->id}}" type="number" min="0" max="900" step="1" value="0" style="width:50px;color:#3c763d;display:none;" />
                </span>
            @endif
            </span>
            </p>
            <p>
            <span id="spent-{{$budget->id}}" data-value="{{$budget->spent}}">Spent: {{mf($budget->spent)}}</span>
            </p>
            </div>
        </div>
    </div>
@endforeach
    <div class="col-lg-3 col-sm-4 col-md-6">
        <div class="panel panel-default">
            <div class="panel-heading">
                Create budget
            </div>
            <div class="panel-body">
                <a href="{{route('budgets.create')}}" class="btn btn-success">Create new budget</a>
            </div>
    </div>
</div>

<!-- DIALOG -->
<div class="modal fade" id="monthlyBudgetModal">
</div><!-- /.modal -->


@stop
@section('scripts')
{{HTML::script('assets/javascript/firefly/budgets.js')}}
@stop