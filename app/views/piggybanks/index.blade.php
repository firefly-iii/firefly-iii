@extends('layouts.default')
@section('content')
<div class="row">
    <div class="col-lg-12 col-md-12 col-sm-12">
    </div>
</div>
<div class="row">
    <div class="col-lg-6 col-md-6 col-sm-12">
        <p class="lead">Save money for large expenses</p>
        <p class="text-info">
            Saving money is <em>hard</em>. Firefly's piggy banks can help you to save money. Simply set the amount
            of money you want to save, set an optional target date and whether or not
            Firefly should remind you to add money
            to the piggy bank.
        </p>
        <p>
            <a href="{{route('piggybanks.create.piggybank')}}" class="btn btn-success">Create new piggy bank</a>
        </p>
    </div>
    <div class="col-lg-6 col-md-6 col-sm-12">
        <p class="lead">Save money for repeated expenses</p>
        <p class="text-info">
            Taxes are due every year. Or maybe you want to save up for your yearly fireworks-binge. Buy a new smart
            phone every three years. Firefly can help you organize these repeated expenses.
        </p>
        <p>
            <a href="{{route('piggybanks.create.repeated')}}" class="btn btn-success">Create new repeated expense</a>
        </p>
    </div>
</div>

<div class="row">
    <div class="col-lg-12 col-md-12 col-sm-12">
        <h3>Current piggy banks</h3>
        @if($countNonRepeating == 0)
        <p class="text-warning">No piggy banks found.</p>
        @else
            @foreach($piggybanks as $piggyBank)
                @if($piggyBank->repeats == 0)
                    <h4><a href="{{route('piggybanks.show',$piggyBank->id)}}">{{{$piggyBank->name}}}</a></h4>
                    <table class="table table-bordered">
                        <tr>
                            <td style="width:10%;">{{mf($piggyBank->currentRelevantRep()->currentamount)}}</td>
                            <td colspan="2">
                                <div class="progress">
                                    <div class="progress-bar
                                    @if($piggyBank->currentRelevantRep()->pct() == 100)
                                        progress-bar-success
                                    @endif
                                    " role="progressbar" aria-valuenow="{{$piggyBank->currentRelevantRep()->pct()}}" aria-valuemin="0" aria-valuemax="100" style="width: {{$piggyBank->currentRelevantRep()->pct()}}%;min-width: 30px;">
                                        {{$piggyBank->currentRelevantRep()->pct()}}%
                                    </div>
                                </div>
                            </td>
                            <td style="width:10%;">{{mf($piggyBank->targetamount)}}</td>
                        </tr>
                        <tr>
                            <td>
                            </td>
                            <td style="width:40%;">
                                <div class="btn-group-xs btn-group">
                                    @if($piggyBank->leftInAccount > 0)
                                    <a data-toggle="modal" href="{{route('piggybanks.amount.add',$piggyBank->id)}}" data-target="#modal" class="btn btn-default"><span class="glyphicon glyphicon-plus-sign"></span> Add money</a>
                                    @endif
                                    @if($piggyBank->currentRelevantRep()->currentamount > 0)
                                    <a data-toggle="modal" href="{{route('piggybanks.amount.remove',$piggyBank->id)}}" data-target="#modal" class="btn btn-default"><span class="glyphicon glyphicon-minus-sign"></span> Remove money</a>
                                    @endif
                                    </div>
                            </td>
                            <td style="width:40%;">
                                <p class="small">
                                @if(!is_null($piggyBank->targetdate))
                                    Target date: {{$piggyBank->targetdate->format('M jS, Y')}}<br />
                                @endif
                                @if(!is_null($piggyBank->reminder))
                                    Next reminder: {{$piggyBank->nextReminderDate()->format('M jS, Y')}} ({{$piggyBank->reminder}})
                                @endif
                                </p>

                            </td>
                            <td>
                                <div class="btn-group btn-group-xs">
                                    <a href="{{route('piggybanks.edit',$piggyBank->id)}}" class="btn btn-default"><span class="glyphicon glyphicon-pencil"></span></a>
                                    <a href="{{route('piggybanks.delete',$piggyBank->id)}}" class="btn btn-danger"><span class="glyphicon glyphicon-trash"></span></a>
                                </div>
                            </td>
                        </tr>
                    </table>
                @endif
            @endforeach
        @endif
    </div>
</div>
<div class="row">
    <div class="col-lg-12 col-md-12 col-sm-12">
        <h3>Current repeated expenses</h3>
            @if($countRepeating == 0)
                <p class="text-warning">No repeated expenses found.</p>
            @else
                @foreach($piggybanks as $repeated)
                    @if($repeated->repeats == 1)
                        <h4><a href="{{route('piggybanks.show',$repeated->id)}}">{{{$repeated->name}}}</a></h4>

                <table class="table table-bordered">
                    <tr>
                        <td style="width:10%;">{{mf($repeated->currentRelevantRep()->currentamount)}}</td>
                        <td colspan="2">
                            <div class="progress">
                                <div class="progress-bar
                                            @if($repeated->currentRelevantRep()->pct() == 100)
                                                progress-bar-success
                                            @endif
                                            " role="progressbar" aria-valuenow="{{$repeated->currentRelevantRep()->pct()}}" aria-valuemin="0" aria-valuemax="100" style="width: {{$repeated->currentRelevantRep()->pct()}}%;min-width: 30px;">
                                    {{$repeated->currentRelevantRep()->pct()}}%
                                </div>
                            </div>
                        </td>
                        <td style="width:10%;">{{mf($repeated->targetamount)}}</td>
                    </tr>
                    <tr>
                        <td>
                        </td>
                        <td style="width:40%;">
                            <div class="btn-group-xs btn-group">
                                @if($repeated->leftInAccount > 0)
                                <a data-toggle="modal" href="{{route('piggybanks.amount.add',$repeated->id)}}" data-target="#modal" class="btn btn-default"><span class="glyphicon glyphicon-plus-sign"></span> Add money</a>
                                @endif
                                @if($repeated->currentRelevantRep()->currentamount > 0)
                                <a data-toggle="modal" href="{{route('piggybanks.amount.remove',$repeated->id)}}" data-target="#modal" class="btn btn-default"><span class="glyphicon glyphicon-minus-sign"></span> Remove money</a>
                                @endif

                            </div>
                        </td>
                        <td style="width:40%;">

                            @if(!is_null($repeated->reminder))
                            <small>
                                Next reminder: {{$repeated->nextReminderDate()->format('M jS, Y')}} ({{$piggyBank->reminder}})
                            </small>
                            @endif

                        </td>
                        <td>
                            <div class="btn-group btn-group-xs">
                                <a href="{{route('piggybanks.edit',$repeated->id)}}" class="btn btn-default"><span class="glyphicon glyphicon-pencil"></span></a>
                                <a href="{{route('piggybanks.delete',$repeated->id)}}" class="btn btn-danger"><span class="glyphicon glyphicon-trash"></span></a>
                            </div>
                        </td>
                    </tr>
                </table>
        @endif
    @endforeach
@endif

    </div>
</div>

<div class="row">
    <div class="col-lg-6">
        <h4>Account information</h4>
        <table class="table">
            <tr>
                <th>Account</th>
                <th>Left for piggy banks</th>
            </tr>
            @foreach($accounts as $account)
            <tr>
                <td>{{{$account['account']->name}}}</td>
                <td>{{mf($account['left'])}}</td>
            </tr>
            @endforeach
        </table>
    </div>
</div>



<!-- MODAL -->
<div class="modal fade" id="modal" tabindex="-1" role="dialog" aria-labelledby="modal" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">

        </div>
    </div>
</div>



@stop
@section('scripts')
<?php // echo javascript_include_tag('piggybanks'); ?>
@stop