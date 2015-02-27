@extends('layouts.default')
@section('content')
{!! Breadcrumbs::renderIfExists(Route::getCurrentRoute()->getName(), $account) !!}
{!! Form::model($account, ['class' => 'form-horizontal','id' => 'update','url' => route('accounts.update',$account->id)]) !!}
<div class="row">
    <div class="col-lg-6 col-md-6 col-sm-12">
        <div class="panel panel-primary">
            <div class="panel-heading">
                <i class="fa {{{$subTitleIcon}}}"></i> Mandatory fields
            </div>
            <div class="panel-body">
                {!! ExpandedForm::text('name') !!}
            </div>
        </div>
        <p>
            <button type="submit" class="btn btn-lg btn-success">
                Update account
            </button>
        </p>
    </div>
    <div class="col-lg-6 col-md-6 col-sm-12">
        <div class="panel panel-default">
            <div class="panel-heading">
                <i class="fa fa-smile-o"></i> Optional fields
            </div>
            <div class="panel-body">
                @if($account->accounttype->type == 'Default account' || $account->accounttype->type == 'Asset account')
                    {!! ExpandedForm::balance('openingBalance',null, ['currency' => $openingBalance ? $openingBalance->transactionCurrency : null]) !!}
                    {!! ExpandedForm::date('openingBalanceDate') !!}
                    {!! ExpandedForm::select('accountRole',Config::get('firefly.accountRoles')) !!}
                    {!! Form::hidden('id',$account->id) !!}
                @endif
                {!! ExpandedForm::checkbox('active','1') !!}
            </div>
        </div>

        <!-- panel for options -->
        <div class="panel panel-default">
            <div class="panel-heading">
                <i class="fa fa-bolt"></i> Options
            </div>
            <div class="panel-body">
                {!! ExpandedForm::optionsList('update','account') !!}
            </div>
        </div>

    </div>
</div>

{!! Form::close() !!}
@stop
