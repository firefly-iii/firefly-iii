@extends('layouts.default')
@section('content')
{{ Breadcrumbs::renderIfExists(Route::getCurrentRoute()->getName(), $piggyBank) }}
<div class="row">
    <div class="col-lg-8 col-md-8 col-sm-6">
        <div class="panel panel-default">
            <div class="panel-heading">
                <i class="fa fa-fw fa-clock-o"></i> Events
            </div>
            <div class="panel-body">
                <div id="piggy-bank-history"></div>
            </div>
        </div>
    </div>
    <div class="col-lg-4 col-md-4 col-sm-6">
            <div class="panel panel-default">
                <div class="panel-heading">
                    <i class="fa fa-fw fa-info-circle"></i> Details

                    <!-- ACTIONS MENU -->
                    <div class="pull-right">
                        <div class="btn-group">
                            <button type="button" class="btn btn-default btn-xs dropdown-toggle" data-toggle="dropdown">
                                Actions
                                <span class="caret"></span>
                            </button>
                            <ul class="dropdown-menu pull-right" role="menu">
                                <li><a href="{{route('piggy_banks.edit',$piggyBank->id)}}"><i class="fa fa-pencil fa-fw"></i> Edit</a></li>
                                <li><a href="{{route('piggy_banks.delete',$piggyBank->id)}}"><i class="fa fa-trash fa-fw"></i> Delete</a></li>
                            </ul>
                        </div>
                    </div>

                </div>
                <div class="panel-body">
                    <table class="table table-bordered table-striped">
                        <tr>
                            <td>Account</td>
                            <td><a href="{{route('accounts.show',$piggyBank->account_id)}}">{{{$piggyBank->account->name}}}</a></td>
                        </tr>
                        <tr>
                            <td>Target amount</td>
                            <td>{{Amount::format($piggyBank->targetamount)}}</td>
                        </tr>
                        <tr>
                            <td>Saved so far</td>
                            <td>{{Amount::format($piggyBank->currentRelevantRep()->currentamount)}}</td>
                        </tr>
                        <tr>
                            <td>Left to save</td>
                            <td>{{Amount::format($piggyBank->targetamount-$piggyBank->currentRelevantRep()->currentamount)}}</td>
                        </tr>
                        <tr>
                            <td>Start date</td>
                            <td>
                                @if(is_null($piggyBank->startdate))
                                    <em>No start date</em>
                                @endif
                                @if(is_object($piggyBank->startdate))
                                    {{$piggyBank->startdate->format('jS F Y')}}
                                @endif
                            </td>
                        </tr>
                        <tr>
                            <td>Target date</td>
                            <td>
                                @if(is_null($piggyBank->targetdate))
                                    <em>No target date</em>
                                @endif
                                @if(is_object($piggyBank->targetdate))
                                    {{$piggyBank->targetdate->format('jS F Y')}}
                                @endif
                            </td>
                        </tr>
                        @if(!is_null($piggyBank->reminder))
                        <tr>
                            <td>Reminder</td>
                            <td>
                                @if(intval($piggyBank->remind_me) == 0)
                                    <em>(no reminder)</em>
                                @else
                                    Every
                                    @if($piggyBank->reminder_skip != 0)
                                        {{$piggyBank->reminder_skip}}
                                    @endif
                                    {{$piggyBank->reminder}}(s)
                                @endif
                            </td>
                        </tr>
                        @endif
                        @if($remindersCount > 0)
                            <tr>
                                <td>Reminders left</td>
                                <td>{{$remindersCount}}</td>
                            </tr>
                            <tr>
                                <td>Expected amount per reminder</td>
                                <td>{{Amount::format($amountPerReminder)}}</td>
                            </tr>
                        @endif
                    </table>
                </div>
            </div>
            <div class="panel panel-default">
                <div class="panel-heading">
                    <i class="fa fa-fw fa-clock-o"></i> Table
                </div>
                <div class="panel-body">
                    @include('list.piggy-bank-events')
                </div>
            </div>
    </div>

</div>
@stop

@section('scripts')
<script type="text/javascript">
var piggyBankID = {{{$piggyBank->id}}};
</script>

<!-- load the libraries and scripts necessary for Google Charts: -->
<script type="text/javascript" src="https://www.google.com/jsapi"></script>
{{HTML::script('assets/javascript/firefly/gcharts.options.js')}}
{{HTML::script('assets/javascript/firefly/gcharts.js')}}
{{HTML::script('assets/javascript/firefly/piggy_banks.js')}}
@stop
