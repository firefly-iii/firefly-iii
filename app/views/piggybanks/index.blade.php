@extends('layouts.default')
@section('content')
<div class="row">
    <div class="col-lg-12 col-md-12 col-sm-12">
        <h1>Firefly
            <small>Piggy banks, large expenses and repeated expenses</small>
        </h1>
    </div>
</div>
<div class="row">
    <div class="col-lg-6 col-md-6 col-sm-12">
        <p class="lead">Save money for large expenses</p>
        <p class="text-info">
            Saving money is <em>hard</em>. Firefly's piggy banks can help you to save money. Simply set the amount
            of money you want to save, set an optional target date and whether or not Firefly should remind you to add money
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
    <div class="col-lg-6 col-md-6 col-sm-12">
        <h3>Current piggy banks</h3>
        @if($countNonRepeating == 0)
        <p class="text-warning">No piggy banks found.</p>
        @else
            <table class="table table-bordered">
            @foreach($piggybanks as $piggyBank)
            @if($piggyBank->repeats == 0)
                <!-- display piggy bank -->
                <tr>
                    <td>
                <h4><a href="{{route('piggybanks.show',$piggyBank->id)}}">{{{$piggyBank->name}}}</a> <small> <span class="label label-default">{{$piggyBank->currentRelevantRep()->pct()}}%</span></small></h4>
                <p>
                        <!-- target amount -->
                        Saving up to {{mf($piggyBank->targetamount)}}.
                        <!-- currently saved -->
                        Currently saved
                        {{mf($piggyBank->currentRelevantRep()->currentamount)}}.

                        <!-- start date (if any) -->
                        @if(!is_null($piggyBank->startdate))
                        Start date: {{$piggyBank->currentRelevantRep()->startdate->format('d M Y')}}.
                        @endif

                        <!-- target date (if any) -->
                        @if(!is_null($piggyBank->targetdate))
                            Target date: {{$piggyBank->currentRelevantRep()->targetdate->format('d M Y')}}.
                        @endif

                        @if(!is_null($piggyBank->reminder))
                            Next reminder: {{$piggyBank->nextReminderDate()->format('d M Y')}}
                        @endif

                </p>
                <div class="btn-group-sm btn-group">
                    <a href="{{route('piggybanks.edit',$piggyBank->id)}}" class="btn btn-default"><span class="glyphicon glyphicon-pencil"></span></a>
                    @if($piggyBank->leftInAccount > 0)
                        <a data-toggle="modal" href="{{route('piggybanks.amount.add',$piggyBank->id)}}" data-target="#modal" class="btn btn-default"><span class="glyphicon glyphicon-plus-sign"></span> Add money</a>
                    @endif
                    @if($piggyBank->currentRelevantRep()->currentamount > 0)
                        <a data-toggle="modal" href="{{route('piggybanks.amount.remove',$piggyBank->id)}}" data-target="#modal" class="btn btn-default"><span class="glyphicon glyphicon-minus-sign"></span> Remove money</a>
                    @endif
                    <a href="{{route('piggybanks.delete',$piggyBank->id)}}" class="btn btn-danger"><span class="glyphicon glyphicon-trash"></span></a>


                </div>
                    </td>
                </tr>

            @endif
            @endforeach
            </table>
        @endif

    </div>
    <div class="col-lg-6 col-md-6 col-sm-12">
        <h3>Current repeated expenses</h3>
        @if($countRepeating == 0)
        <p class="text-warning">No repeated expenses found.</p>
        @else
            <table class="table table-bordered">
            @foreach($piggybanks as $repeated)
            @if($repeated->repeats == 1)
                <!-- display repeated expense -->
                <tr><td>
                        <h4><a href="{{route('piggybanks.show',$repeated->id)}}">{{{$repeated->name}}}</a><small> <span class="label label-default">{{$piggyBank->currentRelevantRep()->pct()}}%</span></small></h4>
                    <p>
                        <!-- target amount -->
                        Saving up to {{mf($repeated->targetamount)}}.

                        <!-- currently saved -->
                        Currently saved
                        {{mf($piggyBank->currentRelevantRep()->currentamount)}}.

                        <!-- start date (if any) -->
                        @if(!is_null($piggyBank->startdate))
                        Start date: {{$piggyBank->currentRelevantRep()->startdate->format('d M Y')}}.
                        @endif

                        <!-- target date (if any) -->
                        @if(!is_null($piggyBank->targetdate))
                        Target date: {{$piggyBank->currentRelevantRep()->targetdate->format('d M Y')}}.
                        @endif

                        @if(!is_null($piggyBank->reminder))
                        Next reminder: {{$piggyBank->nextReminderDate()->format('d M Y')}}
                        @endif


                    </p>
                        <div class="btn-group btn-group-sm">
                            <a href="{{route('piggybanks.edit',$piggyBank->id)}}" class="btn btn-default"><span class="glyphicon glyphicon-pencil"></span></a>
                            @if($piggyBank->leftInAccount > 0)
                                <a data-toggle="modal" href="{{route('piggybanks.amount.add',$piggyBank->id)}}" data-target="#modal" class="btn btn-default"><span class="glyphicon glyphicon-plus-sign"></span> Add money</a>
                            @endif
                            @if($piggyBank->currentRelevantRep()->currentamount > 0)
                                <a data-toggle="modal" href="{{route('piggybanks.amount.remove',$piggyBank->id)}}" data-target="#modal" class="btn btn-default"><span class="glyphicon glyphicon-minus-sign"></span> Remove money</a>
                            @endif
                            <a href="{{route('piggybanks.delete',$piggyBank->id)}}" class="btn btn-danger"><span class="glyphicon glyphicon-trash"></span></a>

                        </div>
                    </td></tr>
            @endif
            @endforeach
            </table>
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
<?php echo javascript_include_tag('piggybanks'); ?>
@stop