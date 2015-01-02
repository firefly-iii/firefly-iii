@extends('layouts.default')
@section('content')
{{ Breadcrumbs::renderIfExists(Route::getCurrentRoute()->getName(), $budget, $repetition) }}
<div class="row">
    <div class="col-lg-9 col-md-9 col-sm-7">
        <div class="panel panel-default">
            <div class="panel-heading">
                Overview
            </div>
            <div class="panel-body">
                <div id="componentOverview"></div>
            </div>
        </div>

         <div class="panel panel-default">
            <div class="panel-heading">
                Transactions
            </div>
                @include('list.journals-full')
        </div>
    </div>
    <div class="col-lg-3 col-md-3 col-sm-5">
        @foreach($limits as $limit)
            @foreach($limit->limitrepetitions as $rep)
                <div class="panel panel-default">
                    <div class="panel-heading">
                        <a href="{{route('budgets.show',[$budget->id,$rep->id])}}">{{$rep->startdate->format('F Y')}}</a>
                    </div>
                    <div class="panel-body">
                        <div class="row">
                            <div class="col-lg-6 col-md-6 col-sm-6">
                                Amount: {{mf($rep->amount)}}
                            </div>
                            <div class="col-lg-6 col-md-6 col-sm-6">
                                Spent: {{mf($rep->spentInRepetition())}}
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-lg-12 col-md-12 col-sm-12">
                                <?php
                                $overspent = $rep->spentInRepetition() > $rep->amount;
                                ?>
                                @if($overspent)
                                <?php
                                $pct = $rep->amount / $rep->spentInRepetition()*100;
                                ?>
                                <div class="progress progress-striped">
                                  <div class="progress-bar progress-bar-success" role="progressbar" aria-valuenow="{{ceil($pct)}}" aria-valuemin="0" aria-valuemax="100" style="width: {{ceil($pct)}}%;"></div>
                                  <div class="progress-bar progress-bar-danger" role="progressbar" aria-valuenow="{{100-ceil($pct)}}" aria-valuemin="0" aria-valuemax="100" style="width: {{100-ceil($pct)}}%;"></div>
                                </div>
                                @else
                                <?php
                                $pct = $rep->spentInRepetition() / $rep->amount*100;
                                ?>
                                <div class="progress progress-striped">
                                  <div class="progress-bar progress-bar-info" role="progressbar" aria-valuenow="{{ceil($pct)}}" aria-valuemin="0" aria-valuemax="100" style="width: {{ceil($pct)}}%;">
                                  </div>
                                </div>
                                @endif
                            </div>
                        </div>
                    </div>
                </div>
            @endforeach
        @endforeach


    </div>
</div>

@stop
@section('scripts')
<script type="text/javascript">
    var componentID = {{$budget->id}};
    @if(!is_null($repetition))
        var repetitionID = {{$repetition->id}};
        var year = {{$repetition->startdate->format('Y')}};
    @else
        var year = {{Session::get('start',\Carbon\Carbon::now()->startOfMonth())->format('Y')}};
    @endif

</script>

<!-- load the libraries and scripts necessary for Google Charts: -->
<script type="text/javascript" src="https://www.google.com/jsapi"></script>
{{HTML::script('assets/javascript/firefly/gcharts.options.js')}}
{{HTML::script('assets/javascript/firefly/gcharts.js')}}
{{HTML::script('assets/javascript/firefly/budgets.js')}}

@stop
