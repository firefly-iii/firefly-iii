@extends('layouts.default')
@section('content')
{{ Breadcrumbs::renderIfExists(Route::getCurrentRoute()->getName(), $journal) }}
{{Form::open(['class' => 'form-horizontal','id' => 'update','url' => route('transactions.update',$journal->id)])}}


<div class="row">
    <div class="col-lg-6 col-md-12 col-sm-12">
    <!-- panel for mandatory fields -->
            <div class="panel panel-primary">
                <div class="panel-heading">
                    <i class="fa fa-exclamation-circle"></i> Mandatory fields
                </div>
                <div class="panel-body">
                    <!-- ALWAYS AVAILABLE -->
                    {{Form::ffText('description',$journal->description)}}

                    <!-- SHOW ACCOUNT (FROM) ONLY FOR WITHDRAWALS AND DEPOSITS -->
                    @if($what == 'deposit' || $what == 'withdrawal')
                        {{Form::ffSelect('account_id',$accounts,$data['account_id'])}}
                    @endif

                    <!-- SHOW EXPENSE ACCOUNT ONLY FOR WITHDRAWALS -->
                    @if($what == 'withdrawal')
                        {{Form::ffText('expense_account',$data['expense_account'])}}
                    @endif
                    <!-- SHOW REVENUE ACCOUNT ONLY FOR DEPOSITS -->
                    @if($what == 'deposit')
                        {{Form::ffText('revenue_account',$data['revenue_account'])}}
                    @endif

                    <!-- ONLY SHOW FROM/TO ACCOUNT WHEN CREATING TRANSFER -->
                    @if($what == 'transfer')
                        {{Form::ffSelect('account_from_id',$accounts,$data['account_from_id'])}}
                        {{Form::ffSelect('account_to_id',$accounts,$data['account_to_id'])}}
                    @endif

                    <!-- ALWAYS SHOW AMOUNT -->
                    {{Form::ffAmount('amount',$data['amount'])}}

                    <!-- ALWAYS SHOW DATE -->
                    {{Form::ffDate('date',$data['date'])}}
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
                    {{Form::ffSelect('budget_id',$budgets,$data['budget_id'])}}
                @endif
                <!-- CATEGORY ALWAYS -->
                {{Form::ffText('category',$data['category'])}}

                <!-- TAGS -->

                <!-- RELATE THIS TRANSFER TO A PIGGY BANK -->
                @if($what == 'transfer' && count($piggies) > 0)
                    {{Form::ffSelect('piggy_bank_id',$piggies,$data['piggy_bank_id'])}}
                @endif
                    </div>
            </div><!-- end of panel for options-->

            <!-- panel for options -->
            <div class="panel panel-default">
                <div class="panel-heading">
                    <i class="fa fa-bolt"></i> Options
                </div>
                <div class="panel-body">
                    {{Form::ffOptionsList('update','transaction')}}
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
{{HTML::script('assets/javascript/firefly/transactions.js')}}
@stop
