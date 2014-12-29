@extends('layouts.default')
@section('content')
{{ Breadcrumbs::renderIfExists(Route::getCurrentRoute()->getName()) }}
{{Form::open(['class' => 'form-horizontal','id' => 'store','url' => route('bills.store')])}}

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
                {{Form::ffDate('date',Carbon\Carbon::now()->addDay()->format('Y-m-d'))}}
                {{Form::ffSelect('repeat_freq',$periods,'monthly')}}
            </div>
        </div>
        <p>
            <button type="submit" class="btn btn-lg btn-success">
                <i class="fa fa-plus-circle"></i> Store new bill
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
                {{Form::ffInteger('skip',0)}}
                {{Form::ffCheckbox('automatch',1,true)}}
                {{Form::ffCheckbox('active',1,true)}}
            </div>
        </div>

        <!-- panel for options -->
        <div class="panel panel-default">
            <div class="panel-heading">
                <i class="fa fa-bolt"></i> Options
            </div>
            <div class="panel-body">
            {{Form::ffOptionsList('create','bill')}}
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