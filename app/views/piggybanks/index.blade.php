@extends('layouts.default')
@section('content')
<div class="row">
    <div class="col-lg-12 col-md-12 col-sm-12">
        <h1>Firefly
            <small>Piggy banks, large expenses and repeated expenses</small>
        </h1>
    </div>
</div>
<div class="row">
    <div class="col-lg-6 col-md-6 col-sm-12">
        <p class="lead">Save money for large expenses</p>
        <p class="text-info">
            Saving money is <em>hard</em>. Firefly's piggy banks can help you to save money. Simply set the amount
            of money you want to save, set an optional target and whether or not Firefly should remind you to add money
            to the piggy bank.
        </p>
        <p>
            <a href="{{route('piggybanks.create.piggybank')}}" class="btn btn-success">Create new piggy bank</a>
        </p>
    </div>
    <div class="col-lg-6 col-md-6 col-sm-12">
        <p class="lead">Save money for repeated expenses</p>
        <p class="text-info">
            Taxes are due every year. Or maybe you want to save up for your yearly fireworks-binge. Buy a new smart
            phone every three years. Firefly can help you organize these repeated expenses.
        </p>
        <p>
            <a href="{{route('piggybanks.create.repeated')}}" class="btn btn-success">Create new repeated expense</a>
        </p>
    </div>
</div>

<div class="row">
    <div class="col-lg-6 col-md-6 col-sm-12">
        <h3>Current piggy banks</h3>
        @if($countNonRepeating == 0)
        <p class="text-warning">No piggy banks found.</p>
        @endif

    </div>
    <div class="col-lg-6 col-md-6 col-sm-12">
        <h3>Current repeated expenses</h3>
        @if($countRepeating == 0)
        <p class="text-warning">No repeated expenses found.</p>
        @endif
    </div>
</div>
@stop
@section('scripts')
<?php echo javascript_include_tag('piggybanks'); ?>
@stop