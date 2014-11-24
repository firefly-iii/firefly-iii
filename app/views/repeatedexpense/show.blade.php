@extends('layouts.default')
@section('content')
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
                Target amount: {{mf($piggyBank->targetamount)}}. Currently saved: {{mf($rep->currentamount)}}.
            </p>
            <div class="row">
            @foreach($rep->bars as $bar)
                <div class="col-lg-{{$barSize}} col-md-{{$barSize}} col-sm-{{$barSize}}">
                    <div class="progress">
                        <!-- currentAmount:{{$bar->getCurrentAmount()}} getAmount:{{$bar->getAmount()}} -->
                            <div class="progress-bar progress-bar-success" role="progressbar" aria-valuenow="{{$bar->percentage()}}" aria-valuemin="0" aria-valuemax="100" style="width: {{$bar->percentage()}}%;"></div>
                            <div class="progress-bar progress-bar-info" role="progressbar" aria-valuenow="{{100-$bar->percentage()}}" aria-valuemin="0" aria-valuemax="100" style="width: {{100-$bar->percentage()}}%;"></div>
                    </div>

                    <p class="small">
                        <!-- {{mf($bar->getAmount())}} -->
                        {{--
                        @if($bar->hasReminder())
                        <a href="{{route('reminders.show',$bar->getReminder()->id)}}">
                            {{DateKit::periodShow($bar->getStartDate(),$piggyBank->reminder)}}
                        </a>
                        @else
                            @if(!is_null($piggyBank->reminder))
                                {{DateKit::periodShow($bar->getStartDate(),$piggyBank->reminder)}}
                            @endif
                        @endif
                        --}}
                        {{$bar->getStartDate()->format('d/m/y')}} &rarr; {{$bar->getTargetDate()->format('d/m/y')}}
                        @if($bar->hasReminder())
                        !
                        @endif
                    </p>
                </div>
            @endforeach
            </div>
        </div>
    </div>
    @endforeach
    @foreach($piggyBank->reminders()->get() as $reminder)
    Reminder: #{{$reminder->id}} [from: {{$reminder->startdate->format('d/m/y')}}, to: {{$reminder->enddate->format('d/m/y')}}]<br />
    @endforeach
</div>
</div>
@stop