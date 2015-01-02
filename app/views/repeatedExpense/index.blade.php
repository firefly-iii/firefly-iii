@extends('layouts.default')
@section('content')
{{ Breadcrumbs::renderIfExists(Route::getCurrentRoute()->getName()) }}
<div class="row">
    <div class="col-lg-12 col-md-12 col-sm-12">
        <p>
        <a class="btn btn-success" href="{{route('repeated.create')}}">Create new repeated expense</a>
        </p>

    </div>
</div>

@foreach($expenses as $entry)
<div class="row">
    <div class="col-lg-12 col-md-12 col-sm-12">
        <div class="panel panel-default">
            <div class="panel-heading">
                <a href="{{route('repeated.show',$entry->id)}}" title="{{{$entry->name}}}">{{{$entry->name}}}</a>
                ({{Amount::format($entry->targetamount)}})

                <!-- ACTIONS MENU -->
                <div class="pull-right">
                    <div class="btn-group">
                        <button type="button" class="btn btn-default btn-xs dropdown-toggle" data-toggle="dropdown">
                            Actions
                            <span class="caret"></span>
                        </button>
                        <ul class="dropdown-menu pull-right" role="menu">
                            <li><a href="{{route('repeated.edit',$entry->id)}}"><i class="fa fa-pencil fa-fw"></i> Edit</a></li>
                            <li><a href="{{route('repeated.delete',$entry->id)}}"><i class="fa fa-trash fa-fw"></i> Delete</a></li>
                        </ul>
                    </div>
                </div>
            </div>
            <div class="panel-body">
                <div class="progress progress-striped">
                    <div class="progress-bar" role="progressbar" aria-valuenow="{{Steam::percentage($entry,$entry->currentRep)}}" aria-valuemin="0" aria-valuemax="100" style="width: {{Steam::percentage($entry,$entry->currentRep)}}%; min-width:15px;">
                        @if(Steam::percentage($entry,$entry->currentRep) > 30)
                            {{Amount::format($entry->currentRep->currentamount,false)}}
                        @endif
                    </div>
                    @if(Steam::percentage($entry,$entry->currentRep) <= 30)
                        &nbsp;<small>{{Amount::format($entry->currentRep->currentamount,false)}}</small>
                    @endif
                </div>
            </div>
            <div class="panel-footer">
                <small>{{$entry->currentRep->startdate->format('j F Y')}} to {{$entry->currentRep->targetdate->format('j F Y')}}</small>
            </div>
        </div>
    </div>
</div>
    @endforeach

<div class="row">
    <div class="col-lg-12 col-md-12 col-sm-12">
        <p>
            <a class="btn btn-success" href="{{route('repeated.create')}}">Create new repeated expense</a>
        </p>

    </div>
</div>




@stop
@section('scripts')
@stop
