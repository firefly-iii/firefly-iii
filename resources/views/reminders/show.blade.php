@extends('layouts.default')
@section('content')
{!! Breadcrumbs::renderIfExists(Route::getCurrentRoute()->getName(), $reminder) !!}
<div class="row">
    <div class="col-lg-6 col-md-6 col-sm-12 col-xs-12">
        <div class="panel panel-default">
            <div class="panel-heading">
                <a href="{{route('reminders.show',$reminder->id)}}">
                @if($reminder->notnow === true)
                    Dismissed reminder
                @else
                    Reminder
                @endif
                for piggy bank "{{$reminder->remindersable->name}}"
                </a>
            </div>
            <div class="panel-body">
                <p>
                    Active between {{$reminder->startdate->format('jS F Y')}}
                    and {{$reminder->enddate->format('jS F Y')}}.
                </p>

                @if(isset($reminder->description))
                    <p>{!! $reminder->description !!}</p>
                @endif
            </div>
            <div class="panel-footer">
                <div class="btn-group">
                    @if($reminder->active === true)
                        <a class="btn btn-warning" href="{{route('reminders.dismiss',$reminder->id)}}">Dismiss</a>
                        <a class="btn btn-success" href="#">Act</a>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>
@stop