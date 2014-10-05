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
                    {{Form::ffText('description')}}
                    @if($what == 'deposit' || $what == 'withdrawal')
                        {{Form::ffSelect('account_id',$accounts)}}
                    @endif


                    <!-- SHOW EXPENSE ACCOUNT ONLY FOR WITHDRAWALS -->
                    @if($what == 'withdrawal')
                        {{Form::ffText('expense_account')}}
                    @endif

                    <!-- SHOW REVENUE ACCOUNT ONLY FOR DEPOSITS -->
                    @if($what == 'deposit')
                        {{Form::ffText('revenue_account')}}
                    @endif


                    <!-- ONLY SHOW FROM/TO ACCOUNT WHEN CREATING TRANSFER -->
                    @if($what == 'transfer')
                        {{Form::ffSelect('account_from_id',$accounts)}}
                        {{Form::ffSelect('account_to_id',$accounts)}}
                    @endif


                    <!-- ALWAYS SHOW AMOUNT -->
                    {{Form::ffNumber('amount')}}

                    <!-- ALWAYS SHOW DATE -->
                    {{Form::ffDate('date', date('Y-m-d'))}}
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
                            {{Form::ffSelect('budget_id',$budgets,0)}}
                        @endif
                        <!-- CATEGORY ALWAYS -->
                        {{Form::ffText('category')}}

                        <!-- TAGS -->


                        <!-- RELATE THIS TRANSFER TO A PIGGY BANK -->
                        @if($what == 'transfer' && count($piggies) > 0)
                            {{Form::ffSelect('piggybank_id',$piggies,0)}}
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