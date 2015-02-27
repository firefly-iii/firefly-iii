@extends('layouts.default')
@section('content')
{!! Breadcrumbs::renderIfExists(Route::getCurrentRoute()->getName(), $repeatedExpense) !!}
{!! Form::model($repeatedExpense, ['class' => 'form-horizontal','id' => 'update','url' => route('repeated.update',$repeatedExpense->id)]) !!}

<input type="hidden" name="id" value="{{$repeatedExpense->id}}" />
<input type="hidden" name="repeats" value="0" />

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
                {!! ExpandedForm::select('rep_length',$periods,null,['label' => 'Repeats every']) !!}
                {!! ExpandedForm::integer('rep_every',null,['label' => 'Skip period']) !!}
                {!! ExpandedForm::integer('rep_times',null,['label' => 'Repeat times']) !!}

            </div>
        </div>
        <p>
            <button type="submit" class="btn btn-lg btn-success">
                <i class="fa fa-pencil"></i> Update repeated expense
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
                {!! ExpandedForm::checkbox('remind_me','1',$preFilled['remind_me'],['label' => 'Remind me']) !!}
                {!! ExpandedForm::select('reminder',$periods,$preFilled['reminder'],['label' => 'Remind every']) !!}
            </div>
        </div>

        <!-- panel for options -->
        <div class="panel panel-default">
            <div class="panel-heading">
                <i class="fa fa-bolt"></i> Options
            </div>
            <div class="panel-body">
                {!! ExpandedForm::optionsList('update','piggy bank') !!}
            </div>
        </div>

    </div>
</div>

{!! Form::close() !!}
@stop
