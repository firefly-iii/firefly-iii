@extends('layouts.default')
@section('content')
{{Form::model($recurringTransaction, ['class' => 'form-horizontal','url' => route('recurring.update', $recurringTransaction->id)])}}

<div class="row">
    <div class="col-lg-6 col-md-12 col-sm-6">
        <!-- panel for mandatory fields -->
        <div class="panel panel-primary">
            <div class="panel-heading">
                <i class="fa fa-exclamation-circle"></i> Mandatory fields
            </div>
            <div class="panel-body">
                {{Form::ffText('name')}}
                {{Form::ffTags('match')}}
                {{Form::ffAmount('amount_min')}}
                {{Form::ffAmount('amount_max')}}
                {{Form::ffDate('date',$recurringTransaction->date->format('Y-m-d'))}}
                {{Form::ffSelect('repeat_freq',$periods)}}
        </div>
    </div>

        <p>
            <button type="submit" class="btn btn-lg btn-success">
                <i class="fa fa-plus-circle"></i> Update recurring transaction
            </button>
        </p>
</div>
    <div class="col-lg-6 col-md-12 col-sm-6">
        <!-- panel for optional fields -->
        <div class="panel panel-default">
            <div class="panel-heading">
                <i class="fa fa-smile-o"></i> Optional fields
            </div>
            <div class="panel-body">
                {{Form::ffInteger('skip')}}
                {{Form::ffCheckbox('automatch',1)}}
                {{Form::ffCheckbox('active',1)}}

            </div>
        </div>
            <div class="panel panel-default">
                <div class="panel-heading">
                    <i class="fa fa-bolt"></i> Options
                </div>
                <div class="panel-body">
                    {{Form::ffOptionsList('update','recurring transaction')}}
                </div>

            </div>
    </div>
</div>
{{Form::close()}}


@stop
@section('styles')
{{HTML::style('assets/stylesheets/tagsinput/bootstrap-tagsinput.css')}}
@stop
@section('scripts')
{{HTML::script('assets/javascript/tagsinput/bootstrap-tagsinput.min.js')}}
@stop