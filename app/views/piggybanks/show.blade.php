@extends('layouts.default')
@section('content')

<div class="row">
    <div class="col-lg-8 col-md-8 col-sm-6">
        <div class="panel panel-default">
            <div class="panel-heading">
                <i class="fa fa-fw fa-clock-o"></i> Events
            </div>
            <div class="panel-body">
                <div id="piggybank-history"></div> <!-- TODO -->
            </div>
        </div>

    </div>
    <div class="col-lg-4 col-md-4 col-sm-6">
            <div class="panel panel-default">
                <div class="panel-heading">
                    <i class="fa fa-fw fa-info-circle"></i> Details
                </div>
                <div class="panel-body">
                    <table class="table table-bordered table-striped">
                        <tr>
                            <td>Account</td>
                            <td><a href="{{route('accounts.show',$piggybank->account_id)}}">{{{$piggybank->account->name}}}</a></td>
                        </tr>
                        <tr>
                            <td>Target amount</td>
                            <td>{{mf($piggybank->targetamount)}}</td>
                        </tr>
                        <tr>
                            <td>Saved so far</td>
                            <td>{{mf(0)}}</td> <!-- TODO -->
                        </tr>
                        <tr>
                            <td>Left to save</td>
                            <td>{{mf(0)}}</td> <!-- TODO -->
                        </tr>
                        <tr>
                            <td>Start date</td>
                            <td>
                                @if(is_null($piggybank->startdate))
                                    <em>No start date</em>
                                @else
                                    {{$piggybank->startdate->format('jS F Y')}}
                                @endif
                            </td>
                        </tr>
                        <tr>
                            <td>Target date</td>
                            <td>
                                @if(is_null($piggybank->targetdate))
                                    <em>No target date</em>
                                @else
                                    {{$piggybank->targetdate->format('jS F Y')}}
                                @endif
                            </td>
                        </tr>
                        <tr>
                            <td>Reminder</td>
                            <td>
                                @if(intval($piggybank->remind_me) == 0)
                                    <em>(no reminder)</em>
                                @else
                                    Every
                                    @if($piggybank->reminder_skip != 0)
                                        {{$piggybank->reminder_skip}}
                                    @endif
                                    {{$piggybank->reminder}}(s)
                                @endif
                            </td>
                        </tr>
                        <tr>
                            <td>Reminders left</td>
                            <td>12</td> <!-- TODO -->
                        </tr>
                        <tr>
                            <td>Expected amount per reminder</td>
                            <td>{{mf(0)}}</td> <!-- TODO -->
                        </tr>
                    </table>
                </div>
            </div>
    </div>

</div>

{{--
<div class="row">
    <div class="col-lg-12 col-md-12 col-sm-12">
        <div class="btn-group">
            <a href="{{route('piggybanks.edit',$piggybank->id)}}" class="btn btn-default"><span class="glyphicon glyphicon-pencil"></span> Edit</a>
            <a href="{{route('piggybanks.delete',$piggybank->id)}}" class="btn btn-danger"><span class="glyphicon glyphicon-trash"></span> Delete</a>

            @if(min(max($balance,$leftOnAccount),$piggybank->targetamount) > 0)
                <a data-toggle="modal" href="{{route('piggybanks.amount.add',$piggybank->id)}}" data-target="#modal" class="btn btn-default"><span class="glyphicon glyphicon-plus-sign"></span> Add money</a>
            @endif

            @if($piggybank->currentRelevantRep()->currentamount > 0)
                <a data-toggle="modal" href="{{route('piggybanks.amount.remove',$piggybank->id)}}" data-target="#modal" class="btn btn-default"><span class="glyphicon glyphicon-minus-sign"></span> Remove money</a>
            @endif
        </div>
    </div>
</div><!-- TODO cleanup for new forms and layout. -->
<div class="row">
    <div class="col-lg-6 col-md-6 col-sm-12">
        <h3>General information</h3>
        <table class="table table-bordered table-striped">
            <tr>
                <th>Field</th>
                <th>Value</th>
            </tr>
            <tr>
                <td>Account</td>
                <td><a href="{{route('accounts.show',$piggybank->account_id)}}">{{{$piggybank->account->name}}}</a></td>
            </tr>
            <tr>
                <td>Target amount</td>
                <td>{{mf($piggybank->targetamount)}}</td>
            </tr>
            <tr>
                <td>Start date</td>
                <td>
                    @if(is_null($piggybank->startdate))
                        <em>No start date</em>
                    @else
                        {{$piggybank->startdate->format('jS F Y')}}
                    @endif
                </td>
            </tr>
            <tr>
                <td>Target date</td>
                <td>
                    @if(is_null($piggybank->targetdate))
                        <em>No target date</em>
                    @else
                        {{$piggybank->targetdate->format('jS F Y')}}
                    @endif
                </td>
            </tr>

            <tr>
                <td>Repeats every</td>
                <td>
                    @if(!is_null($piggybank->rep_length))
                        Every {{$piggybank->rep_every}} {{$piggybank->rep_length}}(s)
                        @if(!is_null($piggybank->rep_times))
                            ({{$piggybank->rep_times}} times)
                        @else
                            (indefinitely)
                        @endif
                    @else
                        <em>Does not repeat</em>
                    @endif
                    </td>
            </tr>
            <tr>
                <td>Reminder</td>
                <td>
                    @if(is_null($piggybank->reminder))
                        <em>(no reminder)</em>
                    @else
                        Every {{$piggybank->reminder_skip}} {{$piggybank->reminder}}(s)
                    @endif
                    </td>
            </tr>
        </table>
    </div>
    <div class="col-lg-6 col-md-6 col-sm-12">
        <h3>Piggy bank instances info</h3>
        @foreach($piggybank->piggybankrepetitions()->orderBy('startdate')->get() as $rep)
        <table class="table table-bordered table-striped">
            <tr>
                <th style="width:50%;">Field</th>
                <th>Value</th>
            </tr>
            <tr>
                <td>ID</td>
                <td>#{{$rep->id}}</td>
            </tr>
            <tr>
                <td>Current amount</td>
                <td>{{mf($rep->currentamount)}} of {{mf($piggybank->targetamount)}}</td>
            </tr>
            <tr>
                <td>Start date</td>
                <td>
                    @if(is_null($rep->startdate))
                        <em>No start date</em>
                    @else
                        {{$rep->startdate->format('jS F Y')}}
                    @endif
                </td>
            </tr>
            <tr>
                <td>Target date</td>
                <td>
                    @if(is_null($rep->targetdate))
                        <em>No target date</em>
                    @else
                        {{$rep->targetdate->format('jS F Y')}}
                    @endif
                </td>
            </tr>
        </table>
        @endforeach
    </div>
</div>
--}}
@stop

@section('scripts')
@stop
