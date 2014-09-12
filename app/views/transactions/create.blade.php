@extends('layouts.default')
@section('content')
<div class="row">
    <div class="col-lg-6 col-md-12 col-sm-12">
        <p class="text-info">
            @if($what == 'withdrawal')
            Some text about moving from asset accounts to expense accounts
            @endif
            @if($what == 'deposit')
            A deposit is when you earn money, moving an amount from a beneficiary into your own account.
            @endif
            @if($what == 'transfer')
            TRANSFER
            @endif
        </p>
    </div>
</div>

{{Form::open(['class' => 'form-horizontal','url' => route('transactions.store',$what)])}}
{{Form::hidden('reminder',Input::get('reminder_id'))}}

<div class="row">
    <div class="col-lg-6 col-md-12 col-sm-12">
        <h4>Mandatory fields</h4>

        <!-- ALWAYS AVAILABLE -->
        <div class="form-group">
            <label for="description" class="col-sm-4 control-label">Description</label>
            <div class="col-sm-8">
                <input type="text" name="description" value="{{{Input::old('description') ?: Input::get('description')}}}" autocomplete="off" class="form-control" placeholder="Description" />
                @if($errors->has('description'))
                    <p class="text-danger">{{$errors->first('description')}}</p>
                @endif
            </div>
        </div>

        <!-- SHOW ACCOUNT (FROM) ONLY FOR WITHDRAWALS AND DEPOSITS -->
        @if($what == 'deposit' || $what == 'withdrawal')
        <div class="form-group">
            <label for="account_id" class="col-sm-4 control-label">
                Asset account
            </label>
            <div class="col-sm-8">
                {{Form::select('account_id',$accounts,Input::old('account_id') ?: Input::get('account_id'),['class' => 'form-control'])}}
                @if($errors->has('account_id'))
                <p class="text-danger">{{$errors->first('account_id')}}</p>
                @endif
            </div>
        </div>
        @endif

        <!-- SHOW EXPENSE ACCOUNT ONLY FOR WITHDRAWALS -->
        @if($what == 'withdrawal')
        <div class="form-group">
            <label for="expense_account" class="col-sm-4 control-label">Expense account</label>
            <div class="col-sm-8">
                <input type="text" name="expense_account" value="{{{Input::old('expense_account')}}}" autocomplete="off" class="form-control" placeholder="Expense account" />
                @if($errors->has('expense_account'))
                <p class="text-danger">{{$errors->first('expense_account')}}</p>
                @else
                    <span class="help-block">This field will auto-complete your existing expense accounts (if any), but you can type freely to create new ones.</span>
                @endif
            </div>
        </div>
        @endif

        <!-- SHOW REVENUE ACCOUNT ONLY FOR DEPOSITS -->
        @if($what == 'deposit')
        <div class="form-group">
            <label for="revenue_account" class="col-sm-4 control-label">
                Revenue account
            </label>
            <div class="col-sm-8">
                <input type="text" name="revenue_account" value="{{{Input::old('revenue_account')}}}" autocomplete="off" class="form-control" placeholder="Revenue account" />
                @if($errors->has('beneficiary'))
                <p class="text-danger">{{$errors->first('revenue_account')}}</p>
                @else
                <span class="help-block">This field will auto-complete your existing revenue accounts (if any), but you can type freely to create new ones.</span>
                @endif
            </div>
        </div>
        @endif

        <!-- ONLY SHOW FROM/TO ACCOUNT WHEN CREATING TRANSFER -->
        @if($what == 'transfer')
        <div class="form-group">
            <label for="account_from_id" class="col-sm-4 control-label">Account from</label>
            <div class="col-sm-8">
                {{Form::select('account_from_id',$accounts,Input::old('account_from_id') ?: Input::get('account_from_id'),['class' => 'form-control'])}}
                @if($errors->has('account_from_id'))
                <p class="text-danger">{{$errors->first('account_from_id')}}</p>
                @endif
            </div>
        </div>

        <div class="form-group">
            <label for="account_to_id" class="col-sm-4 control-label">Account to</label>
            <div class="col-sm-8">
                {{Form::select('account_to_id',$accounts,Input::old('account_to_id') ?: Input::get('account_to_id'),['class' => 'form-control'])}}
                @if($errors->has('account_to_id'))
                <p class="text-danger">{{$errors->first('account_to_id')}}</p>
                @endif
            </div>
        </div>
        @endif

        <!-- ALWAYS SHOW AMOUNT -->
        <div class="form-group">
            <label for="amount" class="col-sm-4 control-label">
                @if($what == 'withdrawal')
                Amount spent
                @endif
                @if($what == 'deposit')
                Amount received
                @endif
                @if($what == 'transfer')
                Amount transferred
                @endif
            </label>
            <div class="col-sm-8">
                <input type="number" name="amount" min="0.01" value="{{Input::old('amount') ?: Input::get('amount')}}" step="any" class="form-control" />
                @if($errors->has('amount'))
                    <p class="text-danger">{{$errors->first('amount')}}</p>
                @endif
            </div>
        </div>

        <!-- ALWAYS SHOW DATE -->
        <div class="form-group">
            <label for="date" class="col-sm-4 control-label">Date</label>
            <div class="col-sm-8">
                <input type="date" name="date" value="{{Input::old('date') ?: date('Y-m-d')}}" class="form-control" />
                @if($errors->has('date'))
                <p class="text-danger">{{$errors->first('date')}}</p>
                @endif
            </div>
        </div>

    </div>
    <div class="col-lg-6 col-md-12 col-sm-12">
        <h4>Optional fields</h4>

        <!-- BUDGET ONLY WHEN CREATING A WITHDRAWAL -->
        @if($what == 'withdrawal')
        <div class="form-group">
            <label for="budget_id" class="col-sm-4 control-label">Budget</label>
            <div class="col-sm-8">
                {{Form::select('budget_id',$budgets,Input::old('budget_id') ?: 0,['class' => 'form-control'])}}
                @if($errors->has('budget_id'))
                <p class="text-danger">{{$errors->first('budget_id')}}</p>
                @else
                <span class="help-block">Select one of your budgets to make this transaction a part of it.</span>
                @endif
            </div>
        </div>
        @endif
        <!-- CATEGORY ALWAYS -->
        <div class="form-group">
            <label for="category" class="col-sm-4 control-label">Category</label>
            <div class="col-sm-8">
                <input type="text" name="category"  value="{{Input::old('category')}}" autocomplete="off" class="form-control" placeholder="Category" />
                @if($errors->has('category'))
                <p class="text-danger">{{$errors->first('category')}}</p>
                @else
                <span class="help-block">Add more fine-grained information to this transaction by entering a category.
                Like the beneficiary-field, this field will auto-complete existing categories but can also be used
                    to create new ones.
                </span>
                @endif
            </div>
        </div>

        <!-- RELATE THIS TRANSFER TO A PIGGY BANK -->
        @if($what == 'transfer' && count($piggies) > 0)
        <div class="form-group">
            <label for="piggybank_id" class="col-sm-4 control-label">
                Piggy bank
            </label>
            <div class="col-sm-8">
                <select name="piggybank_id" class="form-control">
                    <option value="0" label="(no piggy bank)">(no piggy bank)</option>
                    @foreach($piggies as $piggy)
                        @if($piggy->id == Input::old('piggybank_id') || $piggy->id == Input::get('piggybank_id'))
                            <option value="{{$piggy->id}}" label="{{{$piggy->name}}}" selected="selected    ">{{{$piggy->name}}}</option>
                        @else
                    <option value="{{$piggy->id}}" label="{{{$piggy->name}}}">{{{$piggy->name}}}</option>
                        @endif
                    @endforeach
                </select>
                @if($errors->has('piggybank_id'))
                    <p class="text-danger">{{$errors->first('piggybank_id')}}</p>
                @else
                    <span class="help-block">
                        You can directly add the amount you're transferring
                        to one of your piggy banks, provided they are related to the account your
                        transferring <em>to</em>.
                    </span>
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
            <label for="submit" class="col-sm-4 control-label">&nbsp;</label>
            <div class="col-sm-8">
                <div class="checkbox">
                    <label>
                        <input type="checkbox" value="1" name="create" @if(Input::old('create') == '1') checked @endif>
                        Create another (return to this form)
                    </label>
                </div>
            </div>
        </div>
        <!-- ALWAYS SHOW SUBMit -->
        <div class="form-group">
            <label for="submit" class="col-sm-4 control-label">&nbsp;</label>
            <div class="col-sm-8">
                <input type="submit" name="submit" value="Create {{$what}}" class="btn btn-info" />
            </div>
        </div>
    </div>
</div>
{{Form::close()}}

@stop
@section('scripts')
<?php echo javascript_include_tag('transactions'); ?>
@stop