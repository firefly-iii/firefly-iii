@extends('layouts.default')
@section('content')
<div class="row">
    <div class="col-lg-12 col-md-12 col-sm-12">
        <p class="lead">Use categories to group your expenses</p>
    </div>
</div><!-- TODO cleanup to match new theme & form -->

{{Form::open(['class' => 'form-horizontal','url' => route('categories.update',$category->id)])}}


<div class="row">
    <div class="col-lg-6 col-md-12 col-sm-6">
        <h4>Mandatory fields</h4>

        <div class="form-group">
            <label for="name" class="col-sm-4 control-label">Name</label>
            <div class="col-sm-8">
                <input type="text" name="name" class="form-control" id="name" value="{{Input::old('name') ?: $category->name}}" placeholder="Name">
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

        <div class="form-group">
            <div class="col-sm-offset-4 col-sm-8">
                <button type="submit" class="btn btn-default btn-success">Update the category</button>
            </div>
        </div>
    </div>
</div>

{{Form::close()}}


@stop
