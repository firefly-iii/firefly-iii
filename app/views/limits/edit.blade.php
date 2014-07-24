@extends('layouts.default')
@section('content')
<div class="row">
    <div class="col-lg-12 col-md-12 col-sm-12">
        <h1>Firefly
            <small>Edit limit of {{mf($limit->amount,false)}}
                for budget {{{$limit->budget->name}}}

                @if($limit->repeats == 0)
                in {{$limit->limitrepetitions[0]->startdate->format('M Y')}} ({{$limit->repeat_freq}})
                @endif

            </small>
        </h1>
        <p class="text-info">
            Firefly uses an "<a href="http://en.wikipedia.org/wiki/Envelope_System" class="text-success">envelope
                system</a>" for your budgets, which means that for each period of time (for example a month) a virtual
            "envelope" can be created containing a certain amount of money. Money spent within a budget is removed from
            the envelope.
        </p>

        <p class="text-info">
            Firefly gives you the opportunity to create such an envelope when you create a budget. However, you can
            always add more of them.
        </p>
    </div>
</div>

{{Form::open(['class' => 'form-horizontal','url' => route('budgets.limits.update',$limit->id)])}}

<div class="row">
    <div class="col-lg-6 col-md-12 col-sm-6">
        <h4>Mandatory fields</h4>

        <div class="form-group">
            {{ Form::label('budget_id', 'Budget', ['class' => 'col-sm-3 control-label'])}}
            <div class="col-sm-9">
                {{Form::select('budget_id',$budgets,Input::old('budget_id') ?: $limit->component_id, ['class' =>
                'form-control'])}}
                @if($errors->has('budget_id'))
                <p class="text-danger">{{$errors->first('name')}}</p>
                @else
                <span class="help-block">Select one of your existing budgets.</span>
                @endif
            </div>
        </div>

        <div class="form-group">
            {{ Form::label('startdate', 'Start date', ['class' => 'col-sm-3 control-label'])}}
            <div class="col-sm-9">
                <input type="date" name="startdate" value="{{Input::old('startdate') ?: $limit->startdate->format('Y-m-d')}}"
                       class="form-control"/>
                <span class="help-block">This date indicates when the envelope "starts". The date you select
                here will correct itself to the nearest [period] you select below.</span>
            </div>
        </div>

        <div class="form-group">
            <label for="period" class="col-sm-3 control-label">Spending period</label>

            <div class="col-sm-9">
                {{Form::select('period',$periods,Input::old('period') ?: $limit->repeat_freq,['class' => 'form-control'])}}
                <span class="help-block">How long will the envelope last? A week, a month, or even longer?</span>
            </div>
        </div>
        <div class="form-group">
            <label for="period" class="col-sm-3 control-label">Repeat</label>

            <div class="col-sm-9">
                <div class="checkbox">
                    <label>
                        <input type="checkbox" value="1" name="repeats" @if(intval(Input::old('repeats')) == 1 || intval($limit->repeats) == 1) checked @endif>
                        Repeat
                    </label>
                </div>
                <span class="help-block">If you want, Firefly can automatically recreate the "envelope" and fill it again
                when the timespan above has expired. Be careful with this option though. It makes it easier
                    to <a href="http://en.wikipedia.org/wiki/Personal_budget#Concepts">fall back to old habits</a>.
                Instead, you should recreate the envelope yourself each [period].</span>
            </div>
        </div>


        <div class="form-group">
            {{ Form::label('amount', 'Amount', ['class' => 'col-sm-3 control-label'])}}
            <div class="col-sm-9">
                <input type="number" value="{{Input::old('amount') ?: $limit->amount}}" name="amount" min="0.01" step="any" class="form-control"/>
                <span class="help-block">Of course, there needs to be money in the envelope.</span>
            </div>
        </div>

        <div class="form-group">
            {{ Form::label('submit', '&nbsp;', ['class' => 'col-sm-3 control-label'])}}
            <div class="col-sm-9">
                <input type="submit" name="submit" value="Save new limit" class="btn btn-default"/>

            </div>
        </div>

    </div>
</div>

{{Form::open()}}


@stop
@section('scripts')
<script type="text/javascript" src="assets/javascript/moment.min.js"></script>
<script type="text/javascript" src="assets/javascript/limits.js"></script>
@stop