@extends('layouts.default')
@section('content')
{{Form::open(['class' => 'form-horizontal','url' => route('transactions.update',$journal->id)])}}


<div class="row">
    <div class="col-lg-6 col-md-12 col-sm-12">
    <!-- panel for mandatory fields -->
            <div class="panel panel-primary">
                <div class="panel-heading">
                    <i class="fa fa-exclamation-circle"></i> Mandatory fields
                </div>
                <div class="panel-body">
                    <!-- ALWAYS AVAILABLE -->
                    <div class="form-group">
                        <label for="description" class="col-sm-4 control-label">Description</label>
                        <div class="col-sm-8">
                            <input type="text"
                            name="description" value="{{{Input::old('description') ?: $journal->description}}}"
                            autocomplete="off" class="form-control" placeholder="Description" />
                        </div>
                    </div>

                    <!-- SHOW ACCOUNT (FROM) ONLY FOR WITHDRAWALS AND DEPOSITS -->
                    @if($what == 'deposit' || $what == 'withdrawal')
                    <div class="form-group">
                        <label for="account_id" class="col-sm-4 control-label">
                            @if($what == 'deposit')
                            Received into account
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

                    <!-- SHOW EXPENSE ACCOUNT ONLY FOR WITHDRAWALS -->
                    @if($what == 'withdrawal')
                    <div class="form-group">
                        <label for="expense_account" class="col-sm-4 control-label">
                            Expense account
                        </label>
                        <div class="col-sm-8">
                            <input type="text"
                            name="expense_account"
                            value="{{{Input::old('expense_account') ?: $data['expense_account']}}}"
                            autocomplete="off" class="form-control" />
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
                            <input type="text"
                            name="revenue_account"
                            value="{{{Input::old('revenue_account') ?: $data['revenue_account']}}}"
                            autocomplete="off" class="form-control" placeholder="Beneficiary" />
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
        </div> <!-- close panel -->

        <p>
            <button type="submit" class="btn btn-lg btn-success">
                <i class="fa fa-plus-circle"></i> Update {{{$what}}}
            </button>
        </p>

    </div>
    <div class="col-lg-6 col-md-12 col-sm-12">
        <!-- panel for optional fields -->
        <div class="panel panel-default">
            <div class="panel-heading">
                <i class="fa fa-smile-o"></i> Optional fields
            </div>
            <div class="panel-body">
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
                <!-- RELATE THIS TRANSFER TO A PIGGY BANK -->
                @if($what == 'transfer' && count($piggies) > 0)
                <div class="form-group">
                    <label for="piggybank_id" class="col-sm-4 control-label">
                        Piggy bank
                    </label>
                    <div class="col-sm-8">
                        {{Form::select('piggybank_id',$piggies,Input::old('piggybank_id') ?: $data['piggybank_id'],['class' => 'form-control'])}}
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
            </div><!-- end of panel for options-->

            <!-- panel for options -->
            <div class="panel panel-default">
                <div class="panel-heading">
                    <i class="fa fa-bolt"></i> Options
                </div>
                <div class="panel-body">
                    <div class="form-group">
                        <label for="default" class="col-sm-4 control-label">
                        Update
                        </label>
                        <div class="col-sm-8">
                            <div class="radio">
                            <label>
                                {{Form::radio('post_submit_action','store',true)}}
                                Update the {{{$what}}}
                            </label>
                        </div>
                    </div>
                </div>
                    <div class="form-group">
                        <label for="validate_only" class="col-sm-4 control-label">
                        Validate only
                        </label>
                        <div class="col-sm-8">
                            <div class="radio">
                            <label>
                                {{Form::radio('post_submit_action','validate_only')}}
                                Only validate, do not save changes
                            </label>
                        </div>
                    </div>
                </div>
                <div class="form-group">
                    <label for="return_to_form" class="col-sm-4 control-label">
                    Return here
                    </label>
                    <div class="col-sm-8">
                        <div class="radio">
                        <label>
                            {{Form::radio('post_submit_action','return_to_edit')}}
                            After update, return here again.
                        </label>
                    </div>
                </div>
            </div>
            </div>
        </div>
    </div>
</div>
{{Form::close()}}


@stop
@section('scripts')
{{HTML::script('assets/javascript/typeahead/bootstrap3-typeahead.min.js')}}
{{HTML::script('assets/javascript/datatables/jquery.dataTables.min.js')}}
{{HTML::script('assets/javascript/datatables/dataTables.bootstrap.js')}}
{{HTML::script('assets/javascript/firefly/transactions.js')}}
@stop