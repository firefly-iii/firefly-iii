@extends('layouts.default')
@section('content')
{!! Breadcrumbs::renderIfExists(Route::getCurrentRoute()->getName(), $what) !!}
{!! Form::open(['class' => 'form-horizontal','id' => 'store','url' => route('transactions.store',$what)]) !!}
{!! Form::hidden('reminder',Input::get('reminder_id')) !!}

<div class="row">
    <div class="col-lg-6 col-md-12 col-sm-12">
        <!-- panel for mandatory fields -->
        <div class="panel panel-primary">
            <div class="panel-heading">
                <i class="fa fa-exclamation-circle"></i> Mandatory fields
            </div>
            <div class="panel-body">
                    <!-- DESCRIPTION ALWAYS AVAILABLE -->
                    {!! ExpandedForm::text('description') !!}
                    @if($what == 'deposit' || $what == 'withdrawal')
                        {!! ExpandedForm::select('account_id',$accounts) !!}
                    @endif


                    <!-- SHOW EXPENSE ACCOUNT ONLY FOR WITHDRAWALS -->
                    @if($what == 'withdrawal')
                        {{ExpandedForm::text('expense_account')}}
                    @endif

                    <!-- SHOW REVENUE ACCOUNT ONLY FOR DEPOSITS -->
                    @if($what == 'deposit')
                        {{ExpandedForm::text('revenue_account')}}
                    @endif


                    <!-- ONLY SHOW FROM/TO ACCOUNT WHEN CREATING TRANSFER -->
                    @if($what == 'transfer')
                        {{ExpandedForm::select('account_from_id',$accounts)}}
                        {{ExpandedForm::select('account_to_id',$accounts)}}
                    @endif


                    <!-- ALWAYS SHOW AMOUNT -->
                    {{ExpandedForm::amount('amount')}}

                    <!-- ALWAYS SHOW DATE -->
                    {{ExpandedForm::date('date', date('Y-m-d'))}}
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
                            {{ExpandedForm::select('budget_id',$budgets,0)}}
                        @endif
                        <!-- CATEGORY ALWAYS -->
                        {{ExpandedForm::text('category')}}

                        <!-- TAGS -->


                        <!-- RELATE THIS TRANSFER TO A PIGGY BANK -->
                        @if($what == 'transfer' && count($piggies) > 0)
                            {{ExpandedForm::select('piggy_bank_id',$piggies)}}
                        @endif
                    </div>
                </div>
                <!-- panel for options -->
                <div class="panel panel-default">
                    <div class="panel-heading">
                        <i class="fa fa-bolt"></i> Options
                    </div>
                    <div class="panel-body">
                        {{ExpandedForm::optionsList('create','transaction')}}
                    </div>
                </div>
            </div>
        </div>


{{Form::close()}}

@stop
@section('scripts')
<script type="text/javascript" src="js/bootstrap3-typeahead.min.js"></script>
<script type="text/javascript" src="js/transactions.js"></script>
@stop
