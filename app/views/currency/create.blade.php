@extends('layouts.default')
@section('content')
{{ Breadcrumbs::renderIfExists(Route::getCurrentRoute()->getName()) }}
{{Form::open(['class' => 'form-horizontal','id' => 'store','route' => 'currency.store'])}}
<div class="row">
    <div class="col-lg-6 col-md-6 col-sm-12">
        <div class="panel panel-primary">
            <div class="panel-heading">
                <i class="fa {{{$subTitleIcon}}}"></i> Mandatory fields
            </div>
            <div class="panel-body">
                {{Form::ffText('name',null,['maxlength' => 48])}}
                {{Form::ffText('symbol',null,['maxlength' => 8])}}
                {{Form::ffText('code',null,['maxlength' => 3])}}
            </div>
        </div>
        <p>
            <button type="submit" class="btn btn-lg btn-success">
                <i class="fa fa-plus-circle"></i> Store new currency
            </button>
        </p>
    </div>

    <div class="col-lg-6 col-md-6 col-sm-12">

        <!-- panel for options -->
        <div class="panel panel-default">
            <div class="panel-heading">
                <i class="fa fa-bolt"></i> Options
            </div>
            <div class="panel-body">
                {{Form::ffOptionsList('create','currency')}}
            </div>
        </div>

    </div>
</div>

{{Form::close()}}
@stop
