@extends('layouts.default')
@section('content')
<div class="row">
    <div class="col-lg-12 col-md-12 col-sm-12">
        <p class="lead">Use recurring transactions to track repeated expenses</p>
        <p class="text-info">
            Bla bla.
        </p>
    </div>
</div>

{{Form::open(['class' => 'form-horizontal','url' => route('recurring.update', $recurringTransaction->id)])}}

<div class="row">
    <div class="col-lg-6 col-md-12 col-sm-6">
        <h4>Mandatory fields</h4>

        <!-- name -->
        <div class="form-group">
            <label for="name" class="col-sm-4 control-label">Name</label>
            <div class="col-sm-8">
                <input type="text" name="name" class="form-control" id="name"
                       value="{{{Input::old('name') ?: $recurringTransaction->name}}}" placeholder="Name">
                @if($errors->has('name'))
                <p class="text-danger">{{$errors->first('name')}}</p>
                @else
                <span class="help-block">For example: rent, gas, insurance</span>
                @endif
            </div>
        </div>
        <div class="form-group">
            <label for="match" class="col-sm-4 control-label">Matches on</label>
            <div class="col-sm-8">
                <input type="text" name="match" class="form-control" id="match"
                       value="{{Input::old('match') ?: join(',',explode(' ',$recurringTransaction->match))}}"
                       data-role="tagsinput">
                @if($errors->has('match'))
                <p class="text-danger">{{$errors->first('match')}}</p>
                @else
                <span class="help-block">For example: rent, [company name]. All matches need to
                    be present for the recurring transaction to be recognized. This field is not case-sensitive.</span>
                @endif
            </div>
        </div>

        <div class="form-group">
            {{ Form::label('amount_min', 'Minimum amount', ['class' => 'col-sm-4 control-label'])}}
            <div class="col-sm-8">
                <div class="input-group">
                    <span class="input-group-addon">&euro;</span>
                    {{Form::input('number','amount_min', Input::old('amount_min') ?: $recurringTransaction->amount_min,
                    ['step' => 'any', 'class' => 'form-control'])}}
                </div>

                @if($errors->has('amount_min'))
                <p class="text-danger">{{$errors->first('amount_min')}}</p>
                @else
                <span class="help-block">Firefly will only include transactions with a higher amount than this. If your rent
                is usually around &euro; 500,-, enter <code>450</code> to be safe.</span>
                @endif
            </div>
        </div>

        <div class="form-group">
            {{ Form::label('amount_max', 'Maximum amount', ['class' => 'col-sm-4 control-label'])}}
            <div class="col-sm-8">
                <div class="input-group">
                    <span class="input-group-addon">&euro;</span>
                    {{Form::input('number','amount_max', Input::old('amount_max') ?: $recurringTransaction->amount_max,
                    ['step' => 'any', 'class' => 'form-control'])}}
                </div>

                @if($errors->has('amount_max'))
                <p class="text-danger">{{$errors->first('amount_max')}}</p>
                @else
                <span class="help-block">Firefly will only include transactions with a lower amount than this.
                    If your rent is usually around &euro; 500,-, enter <code>550</code> to be safe.</span>
                @endif
            </div>
        </div>

        <div class="form-group">
            {{ Form::label('date', 'Date', ['class' => 'col-sm-4 control-label'])}}
            <div class="col-sm-8">
                {{ Form::input('date','date', Input::old('date') ?: $recurringTransaction->date->format('Y-m-d'),
                ['class' => 'form-control']) }}
                @if($errors->has('date'))
                <p class="text-danger">{{$errors->first('date')}}</p>
                @else
                <span class="help-block">Select the next date you expect the transaction to occur.</span>
                @endif
            </div>
        </div>

        <div class="form-group">
            <label for="period" class="col-sm-4 control-label">Recurrence</label>
            <div class="col-sm-8">
                {{Form::select('repeat_freq',$periods,Input::old('repeat_freq') ?: $recurringTransaction->repeat_freq,
                ['class' => 'form-control'])}}
                @if($errors->has('repeat_freq'))
                <p class="text-danger">{{$errors->first('repeat_freq')}}</p>
                @else
                <span class="help-block">Select the period over which this transaction repeats</span>
                @endif
            </div>
        </div>

    </div>
    <div class="col-lg-6 col-md-12 col-sm-6">
        <h4>Optional fields</h4>

        <div class="form-group">
            {{ Form::label('skip', 'Skip', ['class' => 'col-sm-4 control-label'])}}
            <div class="col-sm-8">
                {{Form::input('number','skip', Input::old('skip') ?: $recurringTransaction->skip,
                ['class' => 'form-control'])}}

                @if($errors->has('skip'))
                <p class="text-danger">{{$errors->first('skip')}}</p>
                @else
                <span class="help-block">Make Firefly skip every <em>n</em> times. Fill in <code>2</code>, and Firefly
                will match, skip, skip and match a transaction.</span>
                @endif
            </div>
        </div>

        <!-- select budget -->



        <!-- select category -->

        <!-- select beneficiary -->

        <div class="form-group">
            <label for="automatch" class="col-sm-4 control-label">Auto-match</label>
            <div class="col-sm-8">
                <div class="checkbox">
                    <label>
                        {{Form::checkbox('automatch',1,Input::old('automatch') == '1' ||
                        (is_null(Input::old('automatch')) && $recurringTransaction->automatch == 1))}}
                        Yes
                    </label>
                </div>
                <span class="help-block">Firefly will automatically match transactions.</span>
            </div>
        </div>

        <div class="form-group">
            <label for="active" class="col-sm-4 control-label">Active</label>
            <div class="col-sm-8">
                <div class="checkbox">
                    <label>
                        {{Form::checkbox('active',1,Input::old('active') == '1' ||
                        (is_null(Input::old('active')) && $recurringTransaction->active == 1))}}
                        Yes
                    </label>
                </div>
                <span class="help-block">This recurring transaction is actually active.</span>
            </div>
        </div>



    </div>
</div>

<div class="row">
    <div class="col-lg-6 col-md-12 col-sm-6">



        <div class="form-group">
            <div class="col-sm-offset-4 col-sm-8">
                <button type="submit" class="btn btn-default btn-success">Update the recurring transaction</button>
            </div>
        </div>
    </div>
</div>

{{Form::close()}}


@stop
@section('styles')
{{HTML::style('assets/stylesheets/tagsinput/bootstrap-tagsinput.css')}}
@stop
@section('scripts')
{{HTML::script('assets/javascript/tagsinput/bootstrap-tagsinput.min.js')}}
@stop