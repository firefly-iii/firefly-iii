@extends('layouts.default')
@section('content')
<div class="row">
    <div class="col-lg-12 col-md-12 col-sm-12">
        <h1>Firefly<br/>
            <small>Welcome!</small>
        </h1>
    </div>
</div>

<div class="row">
    <div class="col-lg-12 col-md-12 col-sm-12">
        <p>
            Welcome to Firefly! To get started, choose either of the two options below.
        </p>
    </div>
</div>

<div class="row">
    <div class="col-lg-6 col-md-6 col-sm-12">
        <h2>Start anew</h2>
        <p>
            Click the link below to create your first account, and get started with Firefly.
        </p>
        <p>
            <a href="#" class="btn btn-info">Start with a new account</a>
        </p>
    </div>

    <div class="col-lg-6 col-md-6 col-sm-12">
        <h2>Migrate from another Firefly</h2>
        <p>
            If you've used Firefly before and have another database around, follow this link to import
            your data from a previous version.
        </p>
        <p>
            <a href="{{route('migrate.index')}}" class="btn btn-info">Import your old data</a>
        </p>
    </div>
</div>
@stop