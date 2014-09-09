@extends('layouts.default')
@section('content')
<div class="row">
    <div class="col-lg-12 col-md-12 col-sm-12">
        <h1>Firefly
            <small>
                @if($piggyBank->repeats == 1)
                Repeated expense
                @else
                Piggy bank
                @endif
                "{{{$piggyBank->name}}}"</small>
        </h1>
        <div class="btn-group">
            <a href="{{route('piggybanks.edit',$piggyBank->id)}}" class="btn btn-default"><span class="glyphicon glyphicon-pencil"></span> Edit</a>
            <a href="{{route('piggybanks.delete',$piggyBank->id)}}" class="btn btn-danger"><span class="glyphicon glyphicon-trash"></span> Delete</a>

            @if(min(max($balance,$leftOnAccount),$piggyBank->targetamount) > 0)
                <a data-toggle="modal" href="{{route('piggybanks.amount.add',$piggyBank->id)}}" data-target="#modal" class="btn btn-default"><span class="glyphicon glyphicon-plus-sign"></span> Add money</a>
            @endif

            @if($piggyBank->currentRelevantRep()->currentamount > 0)
                <a data-toggle="modal" href="{{route('piggybanks.amount.remove',$piggyBank->id)}}" data-target="#modal" class="btn btn-default"><span class="glyphicon glyphicon-minus-sign"></span> Remove money</a>
            @endif
        </div>
    </div>
</div>
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
                <td><a href="{{route('accounts.show',$piggyBank->account_id)}}">{{{$piggyBank->account->name}}}</a></td>
            </tr>
            <tr>
                <td>Target amount</td>
                <td>{{mf($piggyBank->targetamount)}}</td>
            </tr>
            <tr>
                <td>Start date</td>
                <td>
                    @if(is_null($piggyBank->startdate))
                        <em>No start date</em>
                    @else
                        {{$piggyBank->startdate->format('jS F Y')}}
                    @endif
                </td>
            </tr>
            <tr>
                <td>Target date</td>
                <td>
                    @if(is_null($piggyBank->targetdate))
                        <em>No target date</em>
                    @else
                        {{$piggyBank->targetdate->format('jS F Y')}}
                    @endif
                </td>
            </tr>

            <tr>
                <td>Repeats every</td>
                <td>
                    @if(!is_null($piggyBank->rep_length))
                        Every {{$piggyBank->rep_every}} {{$piggyBank->rep_length}}(s)
                        @if(!is_null($piggyBank->rep_times))
                            ({{$piggyBank->rep_times}} times)
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
                    @if(is_null($piggyBank->reminder))
                        <em>(no reminder)</em>
                    @else
                        Every {{$piggyBank->reminder_skip}} {{$piggyBank->reminder}}(s)
                    @endif
                    </td>
            </tr>
        </table>
    </div>
    <div class="col-lg-6 col-md-6 col-sm-12">
        <h3>Piggy bank instances info</h3>
        @foreach($piggyBank->piggybankrepetitions()->orderBy('startdate')->get() as $rep)
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
                <td>{{mf($rep->currentamount)}} of {{mf($piggyBank->targetamount)}}</td>
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

@stop

@section('scripts')
@stop