@extends('layouts.default')
@section('content')
<div class="row">
    <div class="col-lg-12 col-md-12 col-sm-12">
        <h1>Firefly
            <small>Delete "{{{$category->name}}}"</small>
        </h1>
        <p class="lead">
            Remember that deleting something is permanent.
        </p>
    </div>
</div>

{{Form::open(['class' => 'form-horizontal','url' => route('categories.destroy',$category->id)])}}
<div class="row">
    <div class="col-lg-12 col-md-12 col-sm-12">
        @if($category->transactionjournals()->count() > 0)
        <p class="text-info">

            Account "{{{$category->name}}}" still has {{$category->transactionjournals()->count()}} transaction(s) associated to it.
            These will NOT be deleted but will lose their connection to the category.
        </p>
        @endif

        <p class="text-danger">
            Press "Delete permanently" If you are sure you want to delete "{{{$category->name}}}".
        </p>
    </div>

</div>

<div class="row">
    <div class="col-lg-6">
        <div class="form-group">
            <div class="col-sm-8">
                <button type="submit" class="btn btn-default btn-danger">Delete permanently</button>
                    <a href="{{route('categories.index')}}" class="btn-default btn">Cancel</a>
            </div>
        </div>
    </div>
</div>


{{Form::close()}}
@stop