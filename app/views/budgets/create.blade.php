@extends('layouts.default')
@section('content')
<div class="row">
    <div class="col-lg-12 col-md-12 col-sm-12">
        <p class="lead">Use budgets to organize and limit your expenses.</p>
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

{{Form::hidden('from',e(Input::get('from')))}}

<div class="row">
    <div class="col-lg-6 col-md-12 col-sm-6">
        <h4>Mandatory fields</h4>

        <div class="form-group">
            <label for="name" class="col-sm-4 control-label">Name</label>
            <div class="col-sm-8">
                <input type="text" name="name" class="form-control" id="name" value="{{Input::old('name')}}" placeholder="Name">
                @if($errors->has('name'))
                <p class="text-danger">{{$errors->first('name')}}</p>
                @else
                <span class="help-block">For example: groceries, bills</span>
                @endif
            </div>
        </div>

    </div>
    <div class="col-lg-6 col-md-12 col-sm-6">
        <h4>Optional fields</h4>

        <div class="form-group">
            <label for="amount" class="col-sm-4 control-label">Max. amount</label>
            <div class="col-sm-8">
                <div class="input-group">
                    <span class="input-group-addon">&euro;</span>
                    <input type="number" min="0.01" step="any" name="amount" class="form-control" id="amount" value="{{Input::old('amount')}}">
                </div>

                @if($errors->has('amount'))
                <p class="text-danger">{{$errors->first('amount')}}</p>
                @else
                <span class="help-block">What's the most you're willing to spend in this budget? This amount is "put" in the virtual
                envelope.</span>
                @endif
            </div>
        </div>

        <div class="form-group">
            <label for="period" class="col-sm-4 control-label">Spending period</label>
            <div class="col-sm-8">
                {{Form::select('repeat_freq',$periods,Input::old('repeat_freq') ?: 'monthly',['class' => 'form-control'])}}
                @if($errors->has('repeat_freq'))
                <p class="text-danger">{{$errors->first('repeat_freq')}}</p>
                @else
                <span class="help-block">How long will the envelope last? A week, a month, or even longer?</span>
                @endif
            </div>
        </div>

        <div class="form-group">
            <label for="period" class="col-sm-4 control-label">Repeat</label>
            <div class="col-sm-8">
                <div class="checkbox">
                    <label>
                        <input type="checkbox" value="1" name="repeats">
                        Repeat
                    </label>
                </div>
                @if($errors->has('repeats'))
                <p class="text-danger">{{$errors->first('repeats')}}</p>
                @else
                <span class="help-block">If you want, Firefly can automatically recreate the "envelope" and fill it again
                when the timespan above has expired. Be careful with this option though. It makes it easier
                    to <a href="http://en.wikipedia.org/wiki/Personal_budget#Concepts">fall back to old habits</a>.
                Instead, you should recreate the envelope yourself each [period].</span>
                @endif
            </div>
        </div>
    </div>
</div>

<div class="row">
    <div class="col-lg-6 col-md-12 col-sm-6">

        <!-- add another after this one? -->
        <div class="form-group">
            <label for="create" class="col-sm-4 control-label">&nbsp;</label>
            <div class="col-sm-8">
                <div class="checkbox">
                    <label>
                        {{Form::checkbox('create',1,Input::old('create') == '1')}}
                        Create another (return to this form)
                    </label>
                </div>
            </div>
        </div>

        <div class="form-group">
            <div class="col-sm-offset-4 col-sm-8">
                <button type="submit" class="btn btn-default btn-success">Create the budget</button>
            </div>
        </div>
    </div>
</div>

{{Form::close()}}


@stop
