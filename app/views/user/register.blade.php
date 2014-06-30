@extends('layouts.guest')
@section('content')
<div class="row">
    <div class="col-lg-12 col-md-12 col-sm-12">
        <h1>Firefly<br/>
            <small>Register a new account</small>
        </h1>
    </div>
</div>

<div class="row">
    <div class="col-lg-12 col-md-12 col-sm-12">
    </div>
</div>

<div class="row">
    <div class="col-lg-4 col-md-4 col-sm-12">
        {{Form::open()}}
        <div class="form-group">
            <label for="inputEmail">Email address</label>
            <input type="email" class="form-control" id="inputEmail" name="email" placeholder="Enter email">
        </div>
        <button type="submit" class="btn btn-info">Submit</button>
        {{Form::close()}}
    </div>
</div>
<div class="row">
    <div class="col-lg-4 col-md-4 col-sm-12">
        <p>
            &nbsp;
        </p>
        <p>
            <a href="{{route('login')}}" class="btn btn-default">Back to login form</a>
        </p>
        <p>
            <a href="{{route('remindme')}}" class="btn btn-default">Reset password</a>
        </p>

    </div>
</div>

@stop