@extends('layouts.default')
@section('content')
<div class="row">
    <div class="col-lg-12 col-md-12 col-sm-12">
        <h1>Firefly
            <small>Add a new personal account</small>
        </h1>

        <p class="lead">
            Accounts are the record holders for transactions and transfers. Money moves
            from one account to another.
        </p>

        <p class="text-info">
            In a double-entry bookkeeping system (such as this one) there is a "from" account and a "to"
            account, even when money is created from thin air (such as interest, or when new accounts already have
            a positive balance).
        </p>

        <p class="text-info"><span class="text-danger">This form creates personal accounts only.</span>
            If this is your first account, it should be a checking or savings account. Enter its name and if relevant
            the current balance. Check your bank statements for the last current balance you can find.
        </p>
    </div>
</div>

{{Form::open(['class' => 'form-horizontal','route' => 'accounts.store'])}}
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
                <span class="help-block">Use something descriptive such as "checking account" or "My Bank Main Account".</span>
                @endif

            </div>
        </div>

    </div>
    <div class="col-lg-6 col-md-6 col-sm-12">
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
                <button type="submit" class="btn btn-default btn-success">Create the account</button>
            </div>
        </div>
    </div>
</div>


{{Form::close()}}
@stop