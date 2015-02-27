@extends('layouts.default')
@section('content')
{!! Breadcrumbs::renderIfExists(Route::getCurrentRoute()->getName()) !!}
{!! Form::open(['class' => 'form-horizontal','id' => 'store','url' => route('repeated.store')]) !!}

<input type="hidden" name="repeats" value="1" />

<div class="row">
    <div class="col-lg-6 col-md-12 col-sm-6">
        <div class="panel panel-primary">
            <div class="panel-heading">
                <i class="fa fa-fw fa-exclamation"></i> Mandatory fields
            </div>
            <div class="panel-body">

                {!! ExpandedForm::text('name') !!}
                {!! ExpandedForm::select('account_id',$accounts,null,['label' => 'Save on account']) !!}
                {!! ExpandedForm::amount('targetamount') !!}
                {!! ExpandedForm::date('targetdate',null,['label' => 'First target date']) !!}
                {!! ExpandedForm::select('rep_length',$periods,'month',['label' => 'Repeats every']) !!}
                {!! ExpandedForm::integer('rep_every',0,['label' => 'Skip period']) !!}
                {!! ExpandedForm::integer('rep_times',0,['label' => 'Repeat times']) !!}
            </div>
        </div>
        <p>
            <button type="submit" class="btn btn-lg btn-success">
                <i class="fa fa-plus-circle"></i> Store new repeated expense
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

                {!! ExpandedForm::checkbox('remind_me','1',false,['label' => 'Remind me']) !!}
                {!! ExpandedForm::select('reminder',$periods,'month',['label' => 'Remind every']) !!}
            </div>
        </div>

        <!-- panel for options -->
        <div class="panel panel-default">
            <div class="panel-heading">
                <i class="fa fa-bolt"></i> Options
            </div>
            <div class="panel-body">
                {!! ExpandedForm::optionsList('create','repeated expense') !!}
            </div>
        </div>

    </div>
</div>

{!! Form::close() !!}
@stop
