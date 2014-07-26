@extends('layouts.default')
@section('content')
<div class="row">
    <div class="col-lg-12 col-md-12 col-sm-12">
        <h1>Firefly
            <small>Edit transaction "{{{$journal->description}}}"</small>
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
            Updating a transaction will also update balances, budgets and other records.
        </p>
    </div>
</div>

{{Form::open(['class' => 'form-horizontal','url' => route('transactions.update',$journal->id)])}}


<div class="row">
    <div class="col-lg-6 col-md-12 col-sm-12">
        <h4>Mandatory fields</h4>

        <!-- ALWAYS AVAILABLE -->
        <div class="form-group">
            <label for="description" class="col-sm-4 control-label">Description</label>
            <div class="col-sm-8">
                <input type="text" name="description" value="{{{Input::old('description') ?: $journal->description}}}" autocomplete="off" class="form-control" placeholder="Description" />
            </div>
        </div>

        <!-- SHOW ACCOUNT (FROM) ONLY FOR WITHDRAWALS AND DEPOSITS -->
        @if($what == 'deposit' || $what == 'withdrawal')
        <div class="form-group">
            <label for="account_id" class="col-sm-4 control-label">
                @if($what == 'deposit')
                Receiving account
                @endif
                @if($what == 'withdrawal')
                Paid from account
                @endif
            </label>
            <div class="col-sm-8">
                {{Form::select('account_id',$accounts,Input::old('account_id') ?: $data['account_id'],['class' => 'form-control'])}}
            </div>
        </div>
        @endif

        <!-- SHOW BENEFICIARY (ACCOUNT TO) ONLY FOR WITHDRAWALS AND DEPOSITS -->
        @if($what == 'deposit' || $what == 'withdrawal')
        <div class="form-group">
            <label for="beneficiary" class="col-sm-4 control-label">
                @if($what == 'deposit')
                Paying beneficiary
                @endif
                @if($what == 'withdrawal')
                Beneficiary
                @endif
            </label>
            <div class="col-sm-8">
                <input type="text" name="beneficiary" value="{{{Input::old('beneficiary') ?: $data['beneficiary']}}}" autocomplete="off" class="form-control" placeholder="Beneficiary" />
                <span class="help-block">This field will auto-complete your existing beneficiaries (if any), but you can type freely to create new ones.</span>
            </div>
        </div>
        @endif

        <!-- ONLY SHOW FROM/TO ACCOUNT WHEN CREATING TRANSFER -->
        @if($what == 'transfer')
        <div class="form-group">
            <label for="account_from_id" class="col-sm-4 control-label">Account from</label>
            <div class="col-sm-8">
                {{Form::select('account_to_id',$accounts,Input::old('account_from_id') ?: $data['account_from_id'],['class' => 'form-control'])}}
            </div>
        </div>

        <div class="form-group">
            <label for="account_to_id" class="col-sm-4 control-label">Account to</label>
            <div class="col-sm-8">
                {{Form::select('account_from_id',$accounts,Input::old('account_to_id') ?: $data['account_to_id'],['class' => 'form-control'])}}
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
                <input type="number" name="amount" min="0.01" value="{{Input::old('amount') ?: $data['amount']}}" step="any" class="form-control" />
            </div>
        </div>

        <!-- ALWAYS SHOW DATE -->
        <div class="form-group">
            <label for="date" class="col-sm-4 control-label">Date</label>
            <div class="col-sm-8">
                <input type="date" name="date" value="{{Input::old('date') ?: $data['date']}}" class="form-control" />
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
                {{Form::select('budget_id',$budgets,Input::old('budget_id') ?: $data['budget_id'],['class' => 'form-control'])}}
                <span class="help-block">Select one of your budgets to make this transaction a part of it.</span>
            </div>
        </div>
        @endif
        <!-- CATEGORY ALWAYS -->
        <div class="form-group">
            <label for="category" class="col-sm-4 control-label">Category</label>
            <div class="col-sm-8">
                <input type="text" name="category"  value="{{Input::old('category') ?: $data['category']}}" autocomplete="off" class="form-control" placeholder="Category" />
                <span class="help-block">Add more fine-grained information to this transaction by entering a category.
                Like the beneficiary-field, this field will auto-complete existing categories but can also be used
                    to create new ones.
                </span>
            </div>
        </div>

    </div>
</div>

<div class="row">
    <div class="col-lg-6">
        <!-- ALWAYS SHOW SUBMit -->
        <div class="form-group">
            <label for="submit" class="col-sm-4 control-label">&nbsp;</label>
            <div class="col-sm-8">
                <input type="submit" name="submit" value="Update {{$what}}" class="btn btn-info" />
            </div>
        </div>
    </div>
</div>
{{Form::close()}}


@stop
@section('scripts')

    <script type="text/javascript" src="assets/javascript/withdrawal.js"></script>
@stop