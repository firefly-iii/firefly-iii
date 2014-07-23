@extends('layouts.default')
@section('content')
<div class="row">
    <div class="col-lg-12 col-md-12 col-sm-12">
        <h1>Firefly
            <small>Delete limit</small>
        </h1>
    </div>
</div>

{{Form::open(['class' => 'form-horizontal','url' => route('budgets.limits.destroy',$limit->id)])}}

<div class="row">
    <div class="col-lg-12">
        <p class="text-info">
            Destroying a limit (an envelope) does not remove any transactions from the budget.
        </p>
        <p class="text-danger">
            Are you sure?
        </p>

        <div class="form-group">
            <div class="col-sm-8">
                <input type="submit" name="submit" value="Remove limit" class="btn btn-danger" />
            </div>
        </div>

    </div>
</div>


{{Form::open()}}


@stop
@section('scripts')
<script type="text/javascript" src="assets/javascript/moment.min.js"></script>
<script type="text/javascript" src="assets/javascript/limits.js"></script>
@stop