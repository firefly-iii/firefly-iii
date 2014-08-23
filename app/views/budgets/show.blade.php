@extends('layouts.default')
@section('content')
<div class="row">
    <div class="col-lg-12 col-md-12 col-sm-12">
        <h1>Firefly
            <small>Overview for budget "{{{$budget->name}}}"</small>
        </h1>
            <p class="lead">Budgets can help you cut back on spending.</p>
                <!-- warning for selected limit -->
                @if(isset($filters[0]) && is_object($filters[0]) && get_class($filters[0]) == 'Limit')
                <p class="bg-primary" style="padding:15px;">
                    This view is filtered to show only the envelope from
                    {{{$repetitions[0]['limitrepetition']->periodShow()}}},
                    which contains {{mf($repetitions[0]['limit']->amount,false)}}.
                </p>
                <p class="bg-info" style="padding:15px;">
                                    <a href="{{route('budgets.show',$budget->id)}}" class="text-info">Reset the filter(s).</a>
                                </p>
                @endif

                <!-- warning for non-caught only -->
                @if(isset($filters[0]) && $filters[0] == 'no_envelope')
                <p class="bg-primary" style="padding:15px;">
                    This view is filtered to show transactions not in an envelope only.
                </p>
                <p class="bg-info" style="padding:15px;">
                                    <a href="{{route('budgets.show',$budget->id)}}" class="text-info">Reset the filter(s).</a>
                                </p>
                @endif

                <!-- warning for session date -->
                @if($useSessionDates == true)
                <p class="bg-primary" style="padding:15px;">
                    This view is filtered to only show transactions between {{Session::get('start')->format('d M Y')}}
                    and {{Session::get('end')->format('d M Y')}}.
                </p>

                <p class="bg-info" style="padding:15px;">
                    <a href="{{route('budgets.show',$budget->id)}}" class="text-info">Reset the filter(s).</a>
                </p>
                @endif

    </div>
</div>

<div class="row">
    <div class="col-lg-12 col-md-12 col-sm-12">
        <div id="chart" style="height:300px;"></div>
        @if(isset($filters[0]) && is_object($filters[0]) && get_class($filters[0]) == 'Limit')
        <div id="instr" data-type="envelope" data-envelope="{{$repetitions[0]['limitrepetition']->id}}"></div>
        @elseif(isset($filters[0]) && $filters[0] == 'no_envelope')
        <div id="instr" data-type="no_envelope" data-budget="{{$budget->id}}"></div>
        <p class="small text-center">
        A chart showing the date-range of all the not-enveloped stuff, and their amount.
        </p>
        @elseif($useSessionDates == true)
        <div id="instr" data-type="session" data-budget="{{$budget->id}}"></div>
        <p class="small text-center">
        Date range of session, show chart with all expenses in bars
        find all limit repetitions, add them as individual lines and make them go down.
        same as the first but bigger range (potentially).
        </p>
        @else
        <div id="instr" data-type="default" data-budget="{{$budget->id}}"></div>
        <p class="small text-center">(For each visible repetition, a sum of the expense as a bar. A line shows
            the percentage spent for each rep.)</p>
        @endif



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
@section('scripts')
<?php echo javascript_include_tag('budgets'); ?>
@stop