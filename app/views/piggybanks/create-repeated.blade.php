@extends('layouts.default')
@section('content')
<div class="row">
    <div class="col-lg-12 col-md-12 col-sm-12">
        <h1>Firefly
            <small>Create a new repeated expense</small>
        </h1>
        <p class="lead">Create repeated expenses to keep track of long-term planned expenses</p>
    </div>
</div>

{{Form::open(['class' => 'form-horizontal','url' => route('piggybanks.store.repeated')])}}

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
                <span class="help-block">For example: new bike, new camera</span>
                @endif
            </div>
        </div>

        <div class="form-group">
            <label for="account_id" class="col-sm-4 control-label">
                Saving account
            </label>
            <div class="col-sm-8">
                {{Form::select('account_id',$accounts,Input::old('account_id') ?: Input::get('account'),['class' => 'form-control'])}}
                @if($errors->has('account_id'))
                <p class="text-danger">{{$errors->first('account_id')}}</p>
                @else
                <span class="help-block">Indicate on which account you've got your savings.</span>
                @endif
            </div>
        </div>

        <div class="form-group">
            {{ Form::label('targetamount', 'Target amount', ['class' => 'col-sm-4 control-label'])}}
            <div class="col-sm-8">
                <div class="input-group">
                    <span class="input-group-addon">&euro;</span>
                    {{Form::input('number','targetamount', Input::old('targetamount'), ['step' => 'any', 'min' => '1', 'class' => 'form-control'])}}
                </div>

                @if($errors->has('targetamount'))
                <p class="text-danger">{{$errors->first('targetamount')}}</p>
                @else
                <span class="help-block">How much money do you need to save?</span>
                @endif
            </div>
        </div>





    </div>
    <div class="col-lg-6 col-md-12 col-sm-6">

        <div class="no-repeat-piggy">
        <h4>Optional fields for non-repeating piggy banks</h4>
            Fields be here
        </div>
        <div class="repeat-piggy">
            <h4>Optional fields for repeating piggy banks</h4>
            Fields be here.
        </div>

        <!--
        <div class="form-group">
            {{ Form::label('targetdate', 'Target date', ['class' => 'col-sm-4 control-label'])}}
            <div class="col-sm-8">
                {{ Form::input('date','targetdate', Input::old('targetdate') ?: '', ['class'
                => 'form-control']) }}
                @if($errors->has('targetdate'))
                <p class="text-danger">{{$errors->first('targetdate')}}</p>
                @else
                <span class="help-block">
                    If you want to, set a target date. This will inform you how much money you should save to
                    get to the target amount.
                </span>
                @endif
            </div>
        </div>
        -->


    </div>
</div>

<div class="row">
    <div class="col-lg-6 col-md-12 col-sm-6">

        <div class="form-group">
            <div class="col-sm-offset-4 col-sm-8">
                <button type="submit" class="btn btn-default btn-success">Create the piggy bank</button>
            </div>
        </div>
    </div>
</div>

{{Form::close()}}
@stop
@section('scripts')
<?php echo javascript_include_tag('piggybanks-create'); ?>
@stop
