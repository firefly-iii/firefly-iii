@extends('layouts.default')
@section('content')
<div class="row">
    <div class="col-lg-12 col-md-12 col-sm-12">
        <h1>Firefly
            <small>Create a budget</small>
        </h1>
        <p class="text-info">
            Firefly uses the <a href="http://en.wikipedia.org/wiki/Envelope_System" class="text-success">envelope system</a>. Every budget
            is an envelope in which you put money every [period]. Expenses allocated to each budget are paid from this
            (virtual) envelope.
        </p>
        <p class="text-info">
            When the envelope is empty, you must stop spending on the budget. If the envelope still has some money left at the
            end of the [period], congratulations! You have saved money!
        </p>
    </div>
</div>

{{Form::open(['class' => 'form-horizontal','url' => route('budgets.store')])}}

<div class="row">
    <div class="col-lg-6 col-md-12 col-sm-6">
        <h4>Mandatory fields</h4>

        <div class="form-group">
            <label for="name" class="col-sm-3 control-label">Name</label>
            <div class="col-sm-9">
                <input type="text" name="name" class="form-control" id="name" value="{{Input::old('name')}}" placeholder="Name">
                <span class="help-block">For example: groceries, bills</span>
            </div>
        </div>

    </div>
    <div class="col-lg-6 col-md-12 col-sm-6">
        <h4>Optional fields</h4>

        <div class="form-group">
            <label for="amount" class="col-sm-3 control-label">Max. amount</label>
            <div class="col-sm-9">
                <input type="number" min="0.01" step="any" name="amount" class="form-control" id="amount" value="{{Input::old('amount')}}">
                <span class="help-block">What's the most you're willing to spend in this budget? This amount is "put" in the virtual
                envelope.</span>
            </div>
        </div>

        <div class="form-group">
            <label for="period" class="col-sm-3 control-label">Spending period</label>
            <div class="col-sm-9">
                {{Form::select('period',$periods,Input::old('period') ?: 'monthly',['class' => 'form-control'])}}
                <span class="help-block">How long will the envelope last? A week, a month, or even longer?</span>
            </div>
        </div>

        <div class="form-group">
            <label for="period" class="col-sm-3 control-label">Repeat</label>
            <div class="col-sm-9">
                <div class="checkbox">
                    <label>
                        <input type="checkbox" value="1" name="repeats">
                        Repeat
                    </label>
                </div>
                <span class="help-block">If you want, Firefly can automatically recreate the "envelope" and fill it again
                when the timespan above has expired. Be careful with this option though. It makes it easier
                    to <a href="http://en.wikipedia.org/wiki/Personal_budget#Concepts">fall back to old habits</a>.
                Instead, you should recreate the envelope yourself each [period].</span>
            </div>
        </div>
    </div>
</div>

<div class="row">
    <div class="col-lg-6 col-md-12 col-sm-6">
        <input type="submit" name="submit" class="btn btn-info" value="Create new budget" />
        <br /><br /><br /><br />
    </div>
</div>

{{Form::close()}}


@stop
@section('scripts')
    <script type="text/javascript" src="assets/javascript/moment.min.js"></script>
    <script type="text/javascript" src="assets/javascript/limits.js"></script>
@stop