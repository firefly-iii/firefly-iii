@extends('layouts.default')
@section('content')
<div class="row">
    @foreach($journals as $journal)
        <div class="col-lg-4 col-md-4 col-sm-6">
            <div class="panel
            @if($journal->counters->count() > 0)
            panel-default
            @else
            panel-danger
            @endif
            ">
                <div class="panel-heading">
                    <a href="{{route('transactions.show',$journal->id)}}">{{{$journal->description}}}</a>
                </div>
                <div class="panel-body">
                    <p>Spent {{mf($journal->getAmount())}}</p>
                @if($journal->counters->count() > 0)
                </div>
                <table class="table">
                @foreach($journal->counters as $counter)
                <tr>
                    <td><i class="fa fa-fw fa-arrows-h"></i></td>
                    <td><a href="{{route('transactions.show',$counter->id)}}">{{$counter->description}}</a></td>
                    <td>{{mf($counter->getAmount())}}</td>
                </tr>
                <!-- {{$counter}} -->
                @endforeach
                </table>
                @else
                <p class="text-danger">No counter transaction!</p>
                </div>
                @endif
            </div>
        </div>
    @endforeach
</div>


@stop
@section('scripts')
@stop