@extends('layouts.default')
@section('content')
<div class="row">
    <div class="col-lg-12 col-md-12 col-sm-12">
        <h1>Firefly
            <small>Budgets and limits</small>
        </h1>
        <p class="text-info">
            These are your budgets and if set, their "limits". Firefly uses an "<a
                href="http://en.wikipedia.org/wiki/Envelope_System" class="text-success">envelope system</a>" for your
            budgets,
            which means that for each period of time (for example a month) a virtual "envelope" can be created
            containing a certain amount of money. Money spent within a budget is removed from the envelope.

        </p>
    </div>
</div>
<div class="row">
    <div class="col-lg-12 col-md-12 col-sm-12">
        <table class="table table-bordered table-striped">
            <tr>
                <th>Budget</th>
                <th>Current envelope(s)</th>
                <th>&nbsp;</th>
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
                            @if($rep->left() < 0)
                            <span class="label label-danger">
                                <span class="glyphicon glyphicon-envelope"></span>
                                {{mf($rep->left(),false)}}</span>
                            @else
                            <span class="label label-success">
                                <span class="glyphicon glyphicon-envelope"></span>
                                {{mf($rep->left(),false)}}</span>
                            @endif
                        </div>
                        <div class="col-sm-3">
                            <small>
                                @if($limit->repeat_freq == 'monthly')
                                {{$rep->startdate->format('F Y')}}
                                @else
                                NO FORMAT
                                @endif
                            </small>
                        </div>
                        @if($limit->repeats == 1)
                        <div class="col-sm-2">
                            <span class="label label-warning">auto repeats</span>
                        </div>
                        @endif
                        <div class="col-sm-2 @if($limit->repeats == 0) col-sm-offset-2 @endif">
                            <a href="#" class="btn btn-xs btn-default"><span class="glyphicon glyphicon-pencil"></span></a>
                            @if($limit->repeats == 0 || ($limit->repeats == 1 && $index == 0))
                                <a href="#" class="btn btn-xs btn-danger"><span class="glyphicon glyphicon-trash"></span></a>
                            @endif
                        </div>
                    </div>
                    @endforeach
                    @endforeach
                    <p style="margin-top:5px;">
                        <a href="{{route('budgets.limits.create',$budget->id)}}" class="btn btn-default btn-xs"><span
                                class="glyphicon-plus-sign glyphicon"></span> Add another limit</a>
                    </p>
                </td>
                <td>
                    <div class="btn-group btn-group-sm">
                        <a href="#" class="btn btn-default"><span class="glyphicon glyphicon-pencil"></span></a>
                        <a href="#" class="btn btn-danger"><span class="glyphicon glyphicon-trash"></span></a>

                    </div>
                </td>
            </tr>
            @endforeach

        </table>

    </div>
</div>
@stop