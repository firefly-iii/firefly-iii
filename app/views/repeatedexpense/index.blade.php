@extends('layouts.default')
@section('content')
{{ Breadcrumbs::renderIfExists(Route::getCurrentRoute()->getName()) }}
<div class="row">
    <div class="col-lg-12 col-md-12 col-sm-12">
        <p>
        <a class="btn btn-lg btn-success" href="{{route('repeated.create')}}">Create new repeated expense</a>
        </p>

    </div>
</div>
<div class="row">
    <div class="col-lg-12 col-md-12 col-sm-12">

    </div>
</div>
<!-- TODO create update and destroy -->
<div class="row">
@foreach($expenses as $entry)
    <div class="col-lg-3 col-md-4 col-sm-6">
        <div class="panel panel-default">
            <div class="panel-heading">
                <a href="{{route('repeated.show',$entry->id)}}" title="{{{$entry->name}}}">{{{$entry->name}}}</a>
                ({{mf($entry->targetamount)}})
            </div>
            <div class="panel-body">
                <div class="progress progress-striped">
                    <div class="progress-bar" role="progressbar" aria-valuenow="{{Steam::percentage($entry,$entry->currentRep)}}" aria-valuemin="0" aria-valuemax="100" style="width: {{Steam::percentage($entry,$entry->currentRep)}}%; min-width:15px;">
                        @if(Steam::percentage($entry,$entry->currentRep) > 30)
                            {{mf($entry->currentRep->currentamount,false)}}
                        @endif
                    </div>
                    @if(Steam::percentage($entry,$entry->currentRep) <= 30)
                        &nbsp;<small>{{mf($entry->currentRep->currentamount,false)}}</small>
                    @endif
                </div>
            </div>
            <div class="panel-footer">
                <small>{{$entry->currentRep->startdate->format('j F Y')}} to {{$entry->currentRep->targetdate->format('j F Y')}}</small>
            </div>
        </div>
    </div>
    @endforeach
</div>




@stop
@section('scripts')
@stop