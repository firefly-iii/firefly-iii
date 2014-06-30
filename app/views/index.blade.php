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
        <div id="accounts">
        </div>
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
@stop