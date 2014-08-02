@extends('layouts.default')
@section('content')
<div class="row">
    <div class="col-lg-12 col-md-12 col-sm-12">
        <h1>Firefly
            <small>Edit "{{{$account->name}}}"</small>
        </h1>
        <p class="lead">
            Accounts are the record holders for transactions and transfers. Money moves
            from one account to another.
        </p>
    </div>
</div>

{{Form::model($account, ['class' => 'form-horizontal','url' => route('accounts.update',$account->id)])}}
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
                <span
                    class="help-block">Use something descriptive such as "checking account" or "Albert Heijn".</span>
                @endif

            </div>
        </div>

    </div>
    <div class="col-lg-6 col-md-6 col-sm-12">
        @if($account->accounttype->description == 'Default account')
        <h4>Optional fields</h4>

        <div class="form-group">
            {{ Form::label('openingbalance', 'Opening balance', ['class' => 'col-sm-4 control-label'])}}
            <div class="col-sm-8">
                <div class="input-group">
                    <span class="input-group-addon">&euro;</span>
                    @if(!is_null($openingBalance))
                        {{Form::input('number','openingbalance', Input::old('openingbalance') ?: $openingBalance->transactions[1]->amount, ['step' => 'any', 'class' => 'form-control'])}}
                    @else
                        {{Form::input('number','openingbalance', Input::old('openingbalance'), ['step' => 'any', 'class' => 'form-control'])}}
                    @endif

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
                @if(!is_null($openingBalance))
                    {{ Form::input('date','openingbalancedate', Input::old('openingbalancedate') ?: $openingBalance->date->format('Y-m-d'), ['class' => 'form-control']) }}
                @else
                    {{ Form::input('date','openingbalancedate', Input::old('openingbalancedate') ?: date('Y-m-d'), ['class' => 'form-control']) }}
                @endif
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
        <div class="form-group">
            <div class="col-sm-offset-4 col-sm-8">
                <button type="submit" class="btn btn-default btn-success">Update {{{$account->name}}}</button>
            </div>
        </div>
    </div>
</div>


{{Form::close()}}
@stop