@extends('layouts.default')
@section('content')
<div class="row">
    <div class="col-lg-12 col-md-12 col-sm-12">
        <h1>Firefly
            <small>Overview for budget "{{{$budget->name}}}"</small>
        </h1>
        @if(count($filters) == 0)
            <p class="lead">Budgets can help you cut back on spending.</p>
        @else
            <p class="lead">
                @if(isset($filters[0]) && is_object($filters[0]) && get_class($filters[0]) == 'Limit')
                    {{{$repetitions[0]['limitrepetition']->periodShow()}}}, {{mf($repetitions[0]['limit']->amount,false)}}
                @elseif(isset($filters[0]) && $filters[0] == 'no_envelope')
                These transactions are not caught in an envelope.
                @endif
            </p>
        <p class="text-info">
            <a href="{{route('budgets.show',$budget->id)}}">See the whole picture</a>
        </p>
        @endif

    </div>
</div>

<div class="row">
    <div class="col-lg-12 col-md-12 col-sm-12">
        <p class="small text-center">(Some sort of chart here)</p>
    </div>
</div>

@foreach($repetitions as $repetition)
@if(isset($repetition['journals']) && count($repetition['journals']) > 0)
<div class="row">
    <div class="col-lg-12">


            @if($repetition['paginated'] == true)
                <h4>
                    <a href="{{route('budgets.show',$budget->id)}}?noenvelope=true">
                    {{$repetition['date']}}</a> <small>paginated</small></h4>
            @else
                <h4>
                    <a href="{{route('budgets.show',$budget->id)}}?rep={{$repetition['limitrepetition']->id}}">
                        {{$repetition['date']}}
                    </a>
                </h4>
            <small>{{mf($repetition['limit']->amount,false)}} (left: {{mf($repetition['limitrepetition']->left(),false)}})</small>
            @endif
        </h4>
        @if($repetition['paginated'] == true)
            @include('paginated.transactions',['journals' => $repetition['journals'],'highlight' => $highlight])
        @else
            @include('lists.transactions',['journals' => $repetition['journals'],'sum' => true,'highlight' => $highlight])
        @endif
    </div>
</div>
@else
<div class="row">
    <div class="col-lg-12">
        <h4>{{$repetition['date']}}
        </h4>
        <p><em>No transactions</em></p>
    </div>
</div>
@endif
@endforeach

@stop