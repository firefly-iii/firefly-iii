@extends('layouts.default')
@section('content')
{{ Breadcrumbs::renderIfExists(Route::getCurrentRoute()->getName(), $budget) }}
<div class="row">
    <div class="col-lg-12 col-md-12 col-sm-12">
        <p class="lead">Use budgets to organize and limit your expenses.</p>
    </div>
</div>

{{Form::model($budget, ['class' => 'form-horizontal','id' => 'update','url' => route('budgets.update',$budget->id)])}}
<div class="row">
    <div class="col-lg-6 col-md-12 col-sm-6">
        <div class="panel panel-primary">
            <div class="panel-heading">
                <i class="fa fa-fw fa-exclamation"></i> Mandatory fields
            </div>
            <div class="panel-body">
                {{Form::ffText('name')}}
            </div>
        </div>
        <p>
            <button type="submit" class="btn btn-lg btn-success">
                <i class="fa fa-pencil"></i> Update budget
            </button>
        </p>
    </div>
    <div class="col-lg-6 col-md-12 col-sm-6">
        <!-- panel for options -->
        <div class="panel panel-default">
            <div class="panel-heading">
                <i class="fa fa-bolt"></i> Options
            </div>
            <div class="panel-body">
                {{Form::ffOptionsList('update','budget')}}
            </div>
        </div>
    </div>
</div>
{{Form::close()}}


@stop
