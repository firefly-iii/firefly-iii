@extends('layouts.default')
@section('content')
<div class="row">
    <div class="col-lg-12 col-md-12 col-sm-12">
        <h1>Firefly<br/>
            <small>What's playing?</small>
        </h1>
    </div>
</div>
<div class="row">
    <div class="col-lg-12 col-md-12 col-sm-12">
        <h2>Accounts</h2>
        <canvas id="myChart" width="1100" height="300"></canvas>
        <p><small>[settings]</small></p>
    </div>
</div>

<div class="row">
    <div class="col-lg-6 col-md-6 col-sm-12">
        <h3>Expenses</h3>
    </div>
    <div class="col-lg-6 col-md-6 col-sm-12">
        <h3>Budgets</h3>
    </div>
</div>
@stop
@section('scripts')
<script src="assets/javascript/Chart.min.js"></script>
<script src="assets/javascript/index.js"></script>
@stop