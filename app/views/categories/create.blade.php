@extends('layouts.default')
@section('content')
<div class="row">
    <div class="col-lg-12 col-md-12 col-sm-12">
        <p class="lead">Use categories to group your expenses</p>
        <p class="text-info">
            Use categories to group expenses by hobby, for certain types of groceries or what bills are for.
            Expenses grouped in categories do not have to reoccur every month or every week, like budgets.
        </p>
    </div>
</div>

{{Form::open(['class' => 'form-horizontal','url' => route('categories.store')])}}

<div class="row">
    <div class="col-lg-6 col-md-12 col-sm-6">
        <h4>Mandatory fields</h4>

        <div class="form-group">
            <label for="name" class="col-sm-4 control-label">Name</label>
            <div class="col-sm-8">
                <input type="text" name="name" class="form-control" id="name" value="{{Input::old('name')}}" placeholder="Name">
                @if($errors->has('name'))
                <p class="text-danger">{{$errors->first('name')}}</p>
                @else
                <span class="help-block">For example: bike, utilities, daily groceries</span>
                @endif
            </div>
        </div>

    </div>
</div>

<div class="row">
    <div class="col-lg-6 col-md-12 col-sm-6">

        <!-- add another after this one? -->
        <div class="form-group">
            <label for="create" class="col-sm-4 control-label">&nbsp;</label>
            <div class="col-sm-8">
                <div class="checkbox">
                    <label>
                        {{Form::checkbox('create',1,Input::old('create') == '1')}}
                        Create another (return to this form)
                    </label>
                </div>
            </div>
        </div>

        <div class="form-group">
            <div class="col-sm-offset-4 col-sm-8">
                <button type="submit" class="btn btn-default btn-success">Create the category</button>
            </div>
        </div>
    </div>
</div>

{{Form::close()}}


@stop
