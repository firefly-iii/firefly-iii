@extends('layouts.default')
@section('content')
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
@foreach($expenses as $entry)
<div class="row">
    <div class="col-lg-12 col-sm-12 col-md-12">
        <div class="panel panel-default">
            <div class="panel-heading">
                {{{$entry->name}}}
            </div>
            <div class="panel-body">
                <div class="row">
                    @for($i=0;$i<$entry->parts;$i++)
                    <div class="col-lg-{{$entry->barCount}} col-md-{{$entry->barCount}} col-sm-{{$entry->barCount}}">
                        <div class="progress">
                            @if($entry->currentRep->currentamount <= $entry->bars[$i]['amount'])
                                <div class="progress-bar progress-bar-info" role="progressbar" aria-valuenow="100" aria-valuemin="0" aria-valuemax="100" style="width: 100%;">{{mf($entry->bars[$i]['amount'],false)}}</div>
                            @else
                                <div class="progress-bar progress-bar-success" role="progressbar" aria-valuenow="100" aria-valuemin="0" aria-valuemax="100" style="width: 100%;">{{mf($entry->bars[$i]['amount'],false)}}</div>
                            @endif
                        </div>

                    </div>
                    @endfor
                </div>
                <div class="row">
                @for($i=0;$i<$entry->parts;$i++)
                    <div class="col-lg-{{$entry->barCount}} col-md-{{$entry->barCount}} col-sm-{{$entry->barCount}}">
                        <small>{{DateKit::periodShow($entry->bars[$i]['date'],$entry->reminder)}}</small>
                    </div>
                @endfor
                </div>
            </div>
        </div>
    </div>
</div>
@endforeach



@stop
@section('scripts')
@stop