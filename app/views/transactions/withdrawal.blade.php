@extends('layouts.default')
@section('content')
<div class="row">
    <div class="col-lg-12 col-md-12 col-sm-12">
        <h1>Firefly
            <small>Add a new withdrawal</small>
        </h1>
    </div>
</div>
<div class="row">
    <div class="col-lg-6 col-md-12 col-sm-12">
        <p class="text-info">
            Technically speaking, withdrawals, deposits and transfers are all transactions, moving money from
            account <em>A</em> to account <em>B</em>.
        </p>
        <p class="text-info">
            A withdrawal is when you spend money on something, moving an amount to a <em>beneficiary</em>.
        </p>
    </div>
</div>

{{Form::open(['class' => 'form-horizontal'])}}

<div class="row">
    <div class="col-lg-6 col-md-12 col-sm-12">
        <h4>Mandatory fields</h4>

        <div class="form-group">
            <label for="account" class="col-sm-4 control-label">Description</label>
            <div class="col-sm-8">
                <input type="text" name="description" value="{{{Input::old('description')}}}" autocomplete="off" class="form-control" placeholder="Description" />
            </div>
        </div>

        <div class="form-group">
            <label for="account" class="col-sm-4 control-label">Account</label>
            <div class="col-sm-8">
                {{Form::select('account_id',$accounts,Input::old('account_id'),['class' => 'form-control'])}}
            </div>
        </div>

        <div class="form-group">
            <label for="account" class="col-sm-4 control-label">Beneficiary</label>
            <div class="col-sm-8">
                <input type="text" name="beneficiary" value="{{{Input::old('beneficiary')}}}" autocomplete="off" class="form-control" placeholder="Beneficiary" />
                <span class="help-block">This field will auto-complete your existing beneficiaries (if any), but you can type freely to create new ones.</span>
            </div>
        </div>



        <div class="form-group">
            <label for="account" class="col-sm-4 control-label">Amount spent</label>
            <div class="col-sm-8">
                <input type="number" min="0.01" step="any" class="form-control" />
            </div>
        </div>

        <div class="form-group">
            <label for="account" class="col-sm-4 control-label">Date</label>
            <div class="col-sm-8">
                <input type="date" class="form-control" />
            </div>
        </div>

        <div class="form-group">
            <label for="account" class="col-sm-4 control-label">&nbsp;</label>
            <div class="col-sm-8">
                <input type="submit" name="submit" value="Create withdrawal" class="btn btn-info" />
            </div>
        </div>

    </div>
    <div class="col-lg-6 col-md-12 col-sm-12">
        <h4>Optional fields</h4>

        <div class="form-group">
            <label for="account" class="col-sm-4 control-label">Budget</label>
            <div class="col-sm-8">
                <select class="form-control">
                    <option>1</option>
                </select>
                <span class="help-block">Select one of your budgets to make this transaction a part of it.</span>
            </div>
        </div>

        <div class="form-group">
            <label for="account" class="col-sm-4 control-label">Category</label>
            <div class="col-sm-8">
                <input type="text" name="category"  value="" autocomplete="off" class="form-control" placeholder="Category" />
                <span class="help-block">Add more fine-grained information to this transaction by entering a category.
                Like the beneficiary-field, this field will auto-complete existing categories but can also be used
                    to create new ones.
                </span>
            </div>
        </div>

    </div>



@stop
@section('scripts')

    <script type="text/javascript" src="assets/javascript/withdrawal.js"></script>
@stop