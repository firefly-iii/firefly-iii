@extends('layouts.default')
@section('content')
<div class="row">
    <div class="col-lg-12 col-md-12 col-sm-12">
        <h1>Firefly
            <small>Piggy bank "{{{$piggyBank->name}}}"</small>
        </h1>
        <p class="lead">Set targets and save money</p>
        <p class="text-info">
            Saving money is <em>hard</em>. Piggy banks allow you to group money
            from an account together. If you also set a target (and a target date) you
            can save towards your goals.
        </p>
    </div>
</div>
@stop

@section('scripts')
@stop