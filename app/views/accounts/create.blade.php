@extends('layouts.default')
@section('content')
{{Form::open(['class' => 'form-horizontal','route' => 'accounts.store'])}}
{{Form::hidden('what',$what)}}
<div class="row">
    <div class="col-lg-6 col-md-6 col-sm-12">
        <h4>Mandatory fields</h4>

        <div class="form-group">
            {{ Form::label('name', 'Account name', ['class' => 'col-sm-4 control-label'])}}
            <div class="col-sm-8">
                {{ Form::text('name', Input::old('name'), ['class' => 'form-control']) }}
                @if($errors->has('name'))
                    <p class="text-danger">{{$errors->first('name')}}</p>
                @else
                    @if($what == 'asset')
                        <span class="help-block">
                            Use something descriptive such as "checking account" or "My Bank Main Account".
                        </span>
                    @endif
                    @if($what == 'expense')
                        <span class="help-block">
                            Use something descriptive such as "Albert Heijn" or "Amazon".
                        </span>
                    @endif
                    @if($what == 'revenue')
                            <span class="help-block">
                                Use something descriptive such as "my mom" or "my job".
                            </span>
                    @endif
                @endif

            </div>
        </div>

    </div>
    <div class="col-lg-6 col-md-6 col-sm-12">
        @if($what == 'asset')
        <h4>Optional fields</h4>

        <div class="form-group">
            {{ Form::label('openingbalance', 'Opening balance', ['class' => 'col-sm-4 control-label'])}}
            <div class="col-sm-8">
                <div class="input-group">
                    <span class="input-group-addon">&euro;</span>
                    {{Form::input('number','openingbalance', Input::old('openingbalance'), ['step' => 'any', 'class' => 'form-control'])}}
                </div>

                @if($errors->has('openingbalance'))
                <p class="text-danger">{{$errors->first('openingbalance')}}</p>
                @else
                <span class="help-block">What's the current balance of this new account?</span>
                @endif
            </div>
        </div>
        <div class="form-group">
            {{ Form::label('openingbalancedate', 'Opening balance date', ['class' => 'col-sm-4 control-label'])}}
            <div class="col-sm-8">
                {{ Form::input('date','openingbalancedate', Input::old('openingbalancedate') ?: date('Y-m-d'), ['class'
                => 'form-control']) }}
                @if($errors->has('openingbalancedate'))
                <p class="text-danger">{{$errors->first('openingbalancedate')}}</p>
                @else
                <span class="help-block">When was this the balance of the new account? Since your bank statements may lag behind, update this date to match the date of the last known balance of the account.</span>
                @endif
            </div>
        </div>
        @endif

    </div>


</div>

<div class="row">
    <div class="col-lg-6">

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
                <button type="submit" class="btn btn-default btn-success">Create the {{{$what}}} account</button>
            </div>
        </div>
    </div>
</div>


{{Form::close()}}
@stop