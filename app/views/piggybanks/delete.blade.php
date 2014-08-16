@extends('layouts.default')
@section('content')
<div class="row">
    <div class="col-lg-12 col-md-12 col-sm-12">
        <h1>Firefly
            <small>Delete piggy bank</small>
        </h1>
        <p class="lead">Remember that deleting something is permanent.</p>

    </div>

</div>

{{Form::open(['class' => 'form-horizontal','url' => route('piggybanks.destroy',$piggybank->id)])}}

<div class="row">
    <div class="col-lg-6">
        <h4>&nbsp;</h4>
        <p class="text-info">
            This form allows you to delete the piggy bank "{{{$piggybank->name}}}".
        </p>
        <p class="text-info">
            Destroying an envelope does not remove any transactions or accounts.
        </p>
        <p class="text-danger">
            Are you sure?
        </p>

        <div class="form-group">
            <div class="col-sm-8">
                <input type="submit" name="submit" value="Remove piggy bank" class="btn btn-danger" />
                <a href="{{route('piggybanks.index')}}" class="btn-default btn">Cancel</a>
            </div>
        </div>
    </div>
</div>


{{Form::close()}}

@stop
