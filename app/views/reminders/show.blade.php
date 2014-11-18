@extends('layouts.default')
@section('content')
<div class="row">
 <div class="col-lg-8 col-md-8 col-sm-12">
    <div class="panel panel-primary">
        <div class="panel-heading">
            A reminder about
            @if(get_class($reminder->remindersable) == 'Piggybank')
            your piggy bank labelled "{{{$reminder->remindersable->name}}}"
            @endif
        </div>
        <div class="panel-body">
        <p>
            @if(get_class($reminder->remindersable) == 'Piggybank')
                Somewhere between {{$reminder->startdate->format('j F Y')}} and {{$reminder->enddate->format('j F Y')}} you
                should deposit {{mf($amount)}} in piggy bank <a href="{{route('piggybanks.show',$reminder->remindersable_id)}}">{{{$reminder->remindersable->name}}}</a>
                in order to make your goal of saving {{mf($reminder->remindersable->targetamount)}} on {{$reminder->remindersable->targetdate->format('j F Y')}}
            @endif
        </p>
        <p>
            <!-- TODO implement all options -->
            <a href="#" class="btn btn-primary"><i class="fa fa-fw fa-thumbs-o-up"></i> I want to do this</a>
            <a href="#" class="btn btn-success"><i class="fa fa-smile-o fa-fw"></i> I already did this</a>
            <a href="#" class="btn btn-danger"><i class="fa fa-fw fa-clock-o"></i> Not this time</a>

        </p>
        </div>
    </div>
 </div>
</div>
@stop