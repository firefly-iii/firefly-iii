@extends('layouts.default')
@section('content')
{{Form::open(['class' => 'form-horizontal','url' => route('transactions.store',$what)])}}
{{Form::hidden('reminder',Input::get('reminder_id'))}}

<div class="row">
    <div class="col-lg-6 col-md-12 col-sm-12">
        <!-- panel for mandatory fields -->
        <div class="panel panel-primary">
            <div class="panel-heading">
                <i class="fa fa-exclamation-circle"></i> Mandatory fields
            </div>
            <div class="panel-body">
                    <!-- DESCRIPTION ALWAYS AVAILABLE -->
                    <div
                    @if($errors->has('description'))
                        class="form-group has-error has-feedback"
                    @else
                        class="form-group"
                    @endif
                    >
                        <label for="description" class="col-sm-4 control-label">Description</label>
                        <div class="col-sm-8">
                            <input
                                type="text" name="description"
                                value="{{{Input::old('description') ?: Input::get('description')}}}"
                                placeholder="Description"
                                autocomplete="off"
                                class="form-control" />
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
                            <input
                            type="text" name="expense_account" value="{{{Input::old('expense_account')}}}"
                            autocomplete="off" class="form-control" placeholder="Expense account" />
                            @if($errors->has('expense_account'))
                            <p class="text-danger">{{$errors->first('expense_account')}}</p>
                            @else
                                <span class="help-block">
                                This field will auto-complete your existing expense accounts (where you spent the
                                money), but you can type freely to create new ones. If you took the money from
                                an ATM, you should leave this field empty.</span>
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
                            <input type="text"
                            name="revenue_account" value="{{{Input::old('revenue_account')}}}"
                            autocomplete="off" class="form-control" placeholder="Revenue account" />
                            @if($errors->has('beneficiary'))
                            <p class="text-danger">{{$errors->first('revenue_account')}}</p>
                            @else
                            <span class="help-block">
                            This field will auto-complete your existing revenue
                            accounts (where you spent the receive money from),
                            but you can type freely to create new ones.</span>
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
                            <input type="number"
                            name="amount" min="0.01"
                            value="{{Input::old('amount') ?: Input::get('amount')}}"
                            step="any" class="form-control" />
                            @if($errors->has('amount'))
                                <p class="text-danger">{{$errors->first('amount')}}</p>
                            @endif
                        </div>
                    </div>

                    <!-- ALWAYS SHOW DATE -->
                    <div class="form-group">
                        <label for="date" class="col-sm-4 control-label">Date</label>
                        <div class="col-sm-8">
                            <input type="date"
                            name="date" value="{{Input::old('date') ?: date('Y-m-d')}}" class="form-control" />
                            @if($errors->has('date'))
                            <p class="text-danger">{{$errors->first('date')}}</p>
                            @endif
                        </div>
                    </div>
                </div>
        </div>

        <p>
            <button type="submit" class="btn btn-lg btn-success">
                <i class="fa fa-plus-circle"></i> Store new {{{$what}}}
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
                                <span class="help-block">Add more fine-grained information to
                                this transaction by entering a category. This field will auto-complete
                                existing categories but can also be used to create new ones.
                                </span>
                                @endif
                            </div>
                        </div>
                        <!-- TAGS -->


                        <!-- RELATE THIS TRANSFER TO A PIGGY BANK -->
                        @if($what == 'transfer' && count($piggies) > 0)
                        <div class="form-group">
                            <label for="piggybank_id" class="col-sm-4 control-label">
                                Piggy bank
                            </label>
                            <div class="col-sm-8">
                            {{Form::select('piggybank_id',$piggies,Input::old('piggybank_id') ?: 0,['class' => 'form-control'])}}
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
                <!-- panel for options -->
                <div class="panel panel-default">
                    <div class="panel-heading">
                        <i class="fa fa-bolt"></i> Options
                    </div>
                    <div class="panel-body">
                        <div class="form-group">
                            <label for="default" class="col-sm-4 control-label">
                            Store
                            </label>
                            <div class="col-sm-8">
                                <div class="radio">
                                <label>
                                    {{Form::radio('post_submit_action','store',true)}}
                                    Store the {{{$what}}}
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
                                    Only validate, do not save
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
                                {{Form::radio('post_submit_action','create_another')}}
                                After storing, return here to create another one.
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