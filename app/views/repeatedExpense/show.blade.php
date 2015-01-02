@extends('layouts.default')
@section('content')
{{ Breadcrumbs::renderIfExists(Route::getCurrentRoute()->getName(), $repeatedExpense) }}
<div class="row">
<div class="col-lg-12 col-md-12 col-sm-12">
    @foreach($repetitions as $rep)
    <?php
    $barSize = floor(12 / $rep->bars->count()) == 0 ? 1 : floor(12 / $rep->bars->count());
    ?>


    <div class="panel
    @if($today > $rep->startdate && $today < $rep->targetdate)
    panel-primary
    @else
    panel-default
    @endif
    ">
        <div class="panel-heading">
            Repetition from {{$rep->startdate->format('j F Y')}} to {{$rep->targetdate->format('j F Y')}}
        </div>
        <div class="panel-body">
            <p>
                Target amount: {{Amount::format($repeatedExpense->targetamount)}}. Currently saved: {{Amount::format($rep->currentamount)}}.
            </p>
            <div class="row">
            @foreach($rep->bars as $bar)
                <div class="col-lg-{{$barSize}} col-md-{{$barSize}} col-sm-{{$barSize}}">
                    <div class="progress">
                        <!-- currentAmount:{{$bar->getCurrentAmount()}} getAmount:{{$bar->getCumulativeAmount()}} -->
                            <div class="progress-bar progress-bar-success" role="progressbar" aria-valuenow="{{$bar->percentage()}}" aria-valuemin="0" aria-valuemax="100" style="width: {{$bar->percentage()}}%;">
                            @if($bar->percentage() > 50 && $bar->percentage() == 100)
                                @if($bar->hasReminder() && $bar->getReminder()->active == 1)
                                    <a href="{{route('reminders.show',$bar->getReminder()->id)}}" style="color:#fff;"><i class="fa fa-fw fa-clock-o"></i></a>
                                @endif
                                @if($bar->hasReminder() && $bar->getReminder()->active == 0 && $bar->getReminder()->notnow == 0)
                                    <i class="fa fa-fw fa-thumbs-up"></i>
                                @endif
                                @if($bar->hasReminder() && $bar->getReminder()->active == 0 && $bar->getReminder()->notnow == 1)
                                    <i class="fa fa-fw fa-thumbs-down"></i>
                                @endif
                            @endif
                            @if($bar->percentage() > 50 && $bar->percentage() < 100)
                                {{Amount::format($rep->currentamount,false)}}
                            @endif
                            </div>
                            <div class="progress-bar progress-bar-info" role="progressbar" aria-valuenow="{{100-$bar->percentage()}}" aria-valuemin="0" aria-valuemax="100" style="width: {{100-$bar->percentage()}}%;"></div>
                    </div>
                    <p class="small">
                        {{$bar->getStartDate()->format('j F Y')}} &mdash; {{$bar->getTargetDate()->format('j F Y')}}
                    </p>

                </div>
            @endforeach
            </div>
        </div>
    </div>
    @endforeach
</div>
</div>
@stop
