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
<?php
$barSize = floor(12 / $entry->currentRep->bars->count()) == 0 ? 1 : floor(12 / $entry->currentRep->bars->count());
?>
<div class="row">
    <div class="col-lg-12 col-sm-12 col-md-12">
        <div class="panel panel-default">
            <div class="panel-heading">
                <a href="{{route('repeated.show',$entry->id)}}" title="{{{$entry->name}}}">{{{$entry->name}}}</a>
            </div>
            <div class="panel-body">
                <div class="row">
                    <div class="col-lg-12 col-sm-12 col-md-12">
                        <p>
                            Target amount: {{mf($entry->targetamount)}}. Currently saved: {{mf($entry->currentRep->currentamount)}}. Left to save: {{mf($entry->targetamount-$entry->currentRep->currentamount)}}<br />
                            Runs from {{$entry->currentRep->startdate->format('j F Y')}} to {{$entry->currentRep->targetdate->format('j F Y')}}
                        </p>
                    </div>
                </div>
                <div class="row">
                @foreach($entry->currentRep->bars as $bar)
                    <div class="col-lg-{{$barSize}} col-md-{{$barSize}} col-sm-{{$barSize}}">
                        <div class="progress">
                            <!-- currentAmount:{{$bar->getCurrentAmount()}} getAmount:{{$bar->getAmount()}} -->
                                <div class="progress-bar progress-bar-success" role="progressbar" aria-valuenow="{{$bar->percentage()}}" aria-valuemin="0" aria-valuemax="100" style="width: {{$bar->percentage()}}%;"></div>
                                <div class="progress-bar progress-bar-info" role="progressbar" aria-valuenow="{{100-$bar->percentage()}}" aria-valuemin="0" aria-valuemax="100" style="width: {{100-$bar->percentage()}}%;"></div>
                        </div>

                    </div>
                @endforeach
                {{--
                    @for($i=0;$i<$entry->parts;$i++)
                    <!-- {{$entry->currentRep->currentamount}} < {{$entry->bars[$i]['amount']}} -->
                    <div class="col-lg-{{$entry->barCount}} col-md-{{$entry->barCount}} col-sm-{{$entry->barCount}}">
                        <div class="progress">
                            @if($entry->currentRep->currentamount < $entry->bars[$i]['amount'])
                                <!-- TRUE (smaller) -->
                                <div class="progress-bar progress-bar-info" role="progressbar" aria-valuenow="100" aria-valuemin="0" aria-valuemax="100" style="width: 100%;">{{mf($entry->bars[$i]['amount'],false)}}</div>
                            @else
                                <!-- FALSE (larger) -->
                                <div class="progress-bar progress-bar-success" role="progressbar" aria-valuenow="100" aria-valuemin="0" aria-valuemax="100" style="width: 100%;">{{mf($entry->bars[$i]['amount'],false)}}</div>
                            @endif
                        </div>

                    </div>
                    @endfor
                    --}}
                </div>
                <div class="row">
                {{--
                @for($i=0;$i<$entry->parts;$i++)
                    <div class="col-lg-{{$entry->barCount}} col-md-{{$entry->barCount}} col-sm-{{$entry->barCount}}">
                        <small>{{DateKit::periodShow($entry->bars[$i]['date'],$entry->reminder)}}</small>
                    </div>
                @endfor
                --}}
                </div>
            </div>
        </div>
    </div>
</div>
@endforeach



@stop
@section('scripts')
@stop