@extends('layouts.default')
@section('content')
<div class="row">
    <div class="col-lg-12 col-md-12 col-sm-12">
        <h2>Profile<br/>
            <small>Logged in as {{Auth::user()->email}}</small>
        </h1>
    </div>
</div>
<div class="row">
    <div class="col-lg-12 col-md-12 col-sm-12">
        <p class="lead"><a href="{{route('change-password')}}">Change your password</a></p>
    </div>
</div>
@stop
@section('scripts')
@stop