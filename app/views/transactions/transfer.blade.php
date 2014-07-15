@extends('layouts.default')
@section('content')
<div class="row">
    <div class="col-lg-12 col-md-12 col-sm-12">
        <h1>Firefly
            <small>Add a new transfer</small>
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
            A transfer moves money between your own accounts.
        </p>
    </div>
</div>

{{Form::open(['class' => 'form-horizontal'])}}

<div class="row">
    <div class="col-lg-6 col-md-12 col-sm-12">
        <h4>Mandatory fields</h4>

        <div class="form-group">
            <label for="description" class="col-sm-4 control-label">Description</label>
            <div class="col-sm-8">
                <input type="text" name="description" value="{{{Input::old('description')}}}" autocomplete="off" class="form-control" placeholder="Description" />
            </div>
        </div>



        <div class="form-group">
            <label for="account_from_id" class="col-sm-4 control-label">Account from</label>
            <div class="col-sm-8">
                {{Form::select('account_to_id',$accounts,Input::old('account_from_id'),['class' => 'form-control'])}}
            </div>
        </div>

        <div class="form-group">
            <label for="account_to_id" class="col-sm-4 control-label">Account to</label>
            <div class="col-sm-8">
                {{Form::select('account_from_id',$accounts,Input::old('account_to_id'),['class' => 'form-control'])}}
            </div>
        </div>


        <div class="form-group">
            <label for="amount" class="col-sm-4 control-label">Amount spent</label>
            <div class="col-sm-8">
                <input type="number" name="amount" min="0.01" value="{{floatval(Input::old('amount'))}}" step="any" class="form-control" />
            </div>
        </div>

        <div class="form-group">
            <label for="date" class="col-sm-4 control-label">Date</label>
            <div class="col-sm-8">
                <input type="date" name="date" value="{{Input::old('date') ?: date('Y-m-d')}}" class="form-control" />
            </div>
        </div>

        <div class="form-group">
            <label for="submit" class="col-sm-4 control-label">&nbsp;</label>
            <div class="col-sm-8">
                <input type="submit" name="submit" value="Create transfer" class="btn btn-info" />
            </div>
        </div>

    </div>
    <div class="col-lg-6 col-md-12 col-sm-12">
        <h4>Optional fields</h4>

        <div class="form-group">
            <label for="category" class="col-sm-4 control-label">Category</label>
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