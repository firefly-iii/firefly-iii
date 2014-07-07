@extends('layouts.default')
@section('content')
<div class="row">
    <div class="col-lg-8 col-md-8 col-sm-12">
        <h1>Firefly
            <small>Add a new account</small>
        </h1>
        <p class="lead">
            Accounts are the record holders for transactions and transfers. Money moves
            from one account to another.
        </p>
        <p>
            In a double-entry bookkeeping system (such as this one) there is a "from" account and a "to"
            account, even when money is created from thin air (such as interest, or when new accounts already have
            a positive balance).
        </p>
        <p>
            If this is your first account, it should be a checking or savings account. Enter its name and if relevant
            the current balance. Check your bank statements for the last current balance you can find.
        </p>
        {{Form::open(['class' => 'form-horizontal','url' => route('accounts.store')])}}
            <div class="form-group">
                {{ Form::label('name', 'Account name', ['class' => 'col-sm-3 control-label'])}}
                <div class="col-sm-9">
                    {{ Form::text('name', Input::old('name'), ['class' => 'form-control']) }}
                    @if($errors->has('name'))
                    <p class="text-danger">{{$errors->first('name')}}</p>
                    @else
                    <p class="text-info">Use something descriptive such as "checking account" or "My Bank Main Account".</p>
                    @endif

                </div>
            </div>
            <div class="form-group">
                {{ Form::label('openingbalance', 'Opening balance', ['class' => 'col-sm-3 control-label'])}}
                <div class="col-sm-9">
                    {{ Form::input('number','openingbalance', Input::old('openingbalance'), ['step' => 'any', 'class' => 'form-control'])}}
                    @if($errors->has('openingbalance'))
                        <p class="text-danger">{{$errors->first('openingbalance')}}</p>
                    @else
                        <p class="text-info">What's the current balance of this new account?</p>
                    @endif
                </div>
            </div>
            <div class="form-group">
                {{ Form::label('openingbalancedate', 'Opening balance date', ['class' => 'col-sm-3 control-label'])}}
                <div class="col-sm-9">
                    {{ Form::input('date','openingbalancedate', Input::old('openingbalancedate') ?: date('Y-m-d'), ['class' => 'form-control']) }}
                    @if($errors->has('openingbalancedate'))
                        <p class="text-danger">{{$errors->first('openingbalancedate')}}</p>
                    @else
                        <p class="text-info">When was this the balance of the new account? Since your bank statements may lag behind, update this date to match the date of the last known balance of the account.</p>
                    @endif
                </div>
            </div>
            <div class="form-group">
                <div class="col-sm-offset-3 col-sm-9">
                    <button type="submit" class="btn btn-default">Create my first account</button>
                </div>
            </div>
        </form>


    </div>
</div>
@stop