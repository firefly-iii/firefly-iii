@extends('layouts.default')
@section('content')
{{ Breadcrumbs::renderIfExists(Route::getCurrentRoute()->getName(), $category) }}
{{Form::model($category, ['class' => 'form-horizontal','url' => route('categories.update',$category->id)])}}
<div class="row">
    <div class="col-lg-6 col-md-6 col-sm-12">
        <div class="panel panel-primary">
            <div class="panel-heading">
                <i class="fa fa-exclamation"></i> Mandatory fields
            </div>
            <div class="panel-body">
                {{Form::ffText('name')}}
            </div>
        </div>
        <p>
            <button type="submit" class="btn btn-lg btn-success">
                Update category
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
                {{Form::ffOptionsList('update','category')}}
            </div>
        </div>

    </div>
</div>

{{Form::close()}}
@stop