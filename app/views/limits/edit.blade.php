@extends('layouts.default')
@section('content')
<div class="row">
    <div class="col-lg-12 col-md-12 col-sm-12">
        <p class="text-info">
            Firefly uses an "<a href="http://en.wikipedia.org/wiki/Envelope_System" class="text-success">envelope
                system</a>" for your budgets, which means that for each period of time (for example a month) a virtual
            "envelope" can be created containing a certain amount of money. Money spent within a budget is removed from
            the envelope.
        </p>

        <p class="text-info">
            This form allows you to edit the envelope for budget {{{$limit->budget->name}}}, with a content of
            {{mf($limit->amount,false)}}
            @if($limit->repeats == 0)
            in {{$limit->limitrepetitions[0]->startdate->format('M Y')}} ({{$limit->repeat_freq}}).
            @endif
        </p>
    </div>
</div><!-- TODO cleanup and use new forms -->

{{Form::open(['class' => 'form-horizontal','url' => route('budgets.limits.update',$limit->id)])}}
{{Form::hidden('from',e(Input::get('from')))}}
<div class="row">
    <div class="col-lg-6 col-md-12 col-sm-6">
        <h4>Mandatory fields</h4>

        <div class="form-group">
            {{ Form::label('budget_id', 'Budget', ['class' => 'col-sm-4 control-label'])}}
            <div class="col-sm-8">
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
            {{ Form::label('startdate', 'Start date', ['class' => 'col-sm-4 control-label'])}}
            <div class="col-sm-8">
                <input type="date" name="startdate" value="{{Input::old('startdate') ?: $limit->startdate->format('Y-m-d')}}"
                       class="form-control"/>
                <span class="help-block">This date indicates when the envelope "starts". The date you select
                here will correct itself to the nearest [period] you select below.</span>
            </div>
        </div>

        <div class="form-group">
            <label for="period" class="col-sm-4 control-label">Spending period</label>

            <div class="col-sm-8">
                {{Form::select('period',$periods,Input::old('period') ?: $limit->repeat_freq,['class' => 'form-control'])}}
                <span class="help-block">How long will the envelope last? A week, a month, or even longer?</span>
            </div>
        </div>
        <div class="form-group">
            <label for="period" class="col-sm-4 control-label">Repeat</label>

            <div class="col-sm-8">
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
            {{ Form::label('amount', 'Amount', ['class' => 'col-sm-4 control-label'])}}
            <div class="col-sm-8">
                <input type="number" value="{{ is_null(Input::old('amount')) || Input::old('amount') == '' ? $limit->amount : Input::old('amount')}}" name="amount" min="0.01" step="any" class="form-control"/>
                <span class="help-block">Of course, there needs to be money in the envelope.</span>
            </div>
        </div>

        <div class="form-group">
            {{ Form::label('submit', '&nbsp;', ['class' => 'col-sm-4 control-label'])}}
            <div class="col-sm-8">
                <input type="submit" name="submit" value="Update envelope" class="btn btn-success"/>

            </div>
        </div>

    </div>
    @if($limit->repeats == 1)
    <div class="col-lg-6 col-md-6 col-sm-12">
        <h4>Auto repeating</h4>
        <p class="text-info">
            This envelope is set to repeat itself; creating a new period whenever the previous period
            has passed. If you change this envelope, you'll also change the following (automatically created)
            envelopes.
            {{$limit->limitrepetitions()->count() }}
        </p>
        <ul>
            @foreach($limit->limitrepetitions()->orderBy('startdate','DESC')->get() as $rep)
            <li>Evenlope for {{$rep->periodShow()}}, {{mf($rep->amount,false)}}</li>
            @endforeach
        </ul>
    </div>
    @endif
</div>

{{Form::close()}}


@stop
