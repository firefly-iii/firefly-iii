@extends('layouts.default')
@section('content')
<div class="row">
    <div class="col-lg-12 col-md-12 col-sm-12">
        <h1>Firefly
            <small>Edit budget "{{{$budget->name}}}"</small>
        </h1>
        <p class="lead">Use budgets to organize and limit your expenses.</p>
    </div>
</div>

{{Form::open(['class' => 'form-horizontal','url' => route('budgets.update')])}}

{{Form::hidden('id',$budget->id)}}
{{Form::hidden('from',e(Input::get('from')))}}

<div class="row">
    <div class="col-lg-6 col-md-12 col-sm-6">
        <h4>Mandatory fields</h4>

        <div class="form-group">
            <label for="name" class="col-sm-4 control-label">Name</label>
            <div class="col-sm-8">
                <input type="text" name="name" class="form-control" id="name" value="{{Input::old('name') ?: $budget->name}}" placeholder="Name">
                @if($errors->has('name'))
                <p class="text-danger">{{$errors->first('name')}}</p>
                @else
                <span class="help-block">For example: groceries, bills</span>
                @endif
            </div>
        </div>

    </div>

</div>

<div class="row">
    <div class="col-lg-6 col-md-12 col-sm-6">

        <div class="form-group">
            <div class="col-sm-offset-4 col-sm-8">
                <button type="submit" class="btn btn-default btn-success">Update the budget</button>
            </div>
        </div>
    </div>
</div>

{{Form::close()}}


@stop
