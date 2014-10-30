@extends('layouts.default')
@section('content')
<div class="row"><!-- TODO cleanup to match new theme & form -->
    <div class="col-lg-12 col-md-12 col-sm-12">
            <p class="lead">Budgets can help you cut back on spending.</p>

                @if($view == 1)
                <!-- warning for selected limit -->
                <p class="bg-primary" style="padding:15px;">
                    This view is filtered to show only the envelope from
                    {{{$repetitions[0]['limitrepetition']->periodShow()}}},
                    which contains {{mf($repetitions[0]['limit']->amount,false)}}.
                </p>

                @endif


                @if($view == 2)
                <!-- warning for non-caught only -->
                <p class="bg-primary" style="padding:15px;">
                    This view is filtered to show transactions not in an envelope only.
                </p>
                @endif

                @if($view == 3)
                <!-- warning for session date -->
                <p class="bg-primary" style="padding:15px;">
                    This view is filtered to only show transactions between {{Session::get('start')->format('d M Y')}}
                    and {{Session::get('end')->format('d M Y')}}.
                </p>
                @endif
        @if($view != 4)
        <p class="bg-info" style="padding:15px;">
            <a class="btn btn-default btn-sm" href="{{route('budgets.show',$budget->id)}}">Reset the filter</a>
        </p>
        @endif

    </div>
</div>

<div class="row">
    <div class="col-lg-12 col-md-12 col-sm-12">
        <div id="chart"><img src="http://placehold.it/650x300" title="Placeholder" alt="" /></div>
        @if($view == 1)
        <div id="instr" data-type="envelope" data-envelope="{{$repetitions[0]['limitrepetition']->id}}"></div>
        @endif


        @if($view == 2)
        <div id="instr" data-type="no_envelope" data-budget="{{$budget->id}}"></div>
        @endif

        @if($view == 3)
        <div id="instr" data-type="session" data-budget="{{$budget->id}}"></div>
        @endif

        @if($view == 4)
        <div id="instr" data-type="default" data-budget="{{$budget->id}}"></div>
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
                    <a href="{{route('budgets.show',$budget->id,$repetition['limitrepetition']->id)}}">
                        {{$repetition['date']}}
                    </a>
                </h4>
            <small>{{mf($repetition['limit']->amount,false)}}
            (left: {{mf($repetition['limitrepetition']->leftInRepetition(),false)}})</small>
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
@if($view == 1)
{{HTML::script('assets/javascript/firefly/budgets/limit.js')}}
@endif

@if($view == 2)
{{HTML::script('assets/javascript/firefly/budgets/nolimit.js')}}
@endif

@if($view == 3)
{{HTML::script('assets/javascript/firefly/budgets/session.js')}}
@endif
@if($view == 4)
{{HTML::script('assets/javascript/firefly/budgets/default.js')}}
@endif

@stop