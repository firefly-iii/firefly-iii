@extends('layouts.default')
@section('content')

<div class="row">
    <div class="col-lg-8 col-md-8 col-sm-6">
        <div class="panel panel-default">
            <div class="panel-heading">
                <i class="fa fa-fw fa-clock-o"></i> Events
            </div>
            <div class="panel-body">
                <div id="piggybank-history"></div> <!-- TODO piggy bank events -->
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
                            <td>{{mf($piggybank->currentRelevantRep()->currentamount)}}</td>
                        </tr>
                        <tr>
                            <td>Left to save</td>
                            <td>{{mf($piggybank->targetamount-$piggybank->currentRelevantRep()->currentamount)}}</td>
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
                            <td>12</td> <!-- TODO piggy bank reminders-->
                        </tr>
                        <tr>
                            <td>Expected amount per reminder</td>
                            <td>{{mf(0)}}</td> <!-- TODO piggy bank reminder -->
                        </tr>
                    </table>
                </div>
            </div>
    </div>

</div>
@stop

@section('scripts')
@stop
