@extends('layouts.guest')
@section('content')
<div class="row">
    <div class="col-lg-12 col-md-12 col-sm-12">
        <h1>Firefly<br/>
            <small>Login</small>
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
        <div class="form-group">
            <label for="inputPassword">Password</label>
            <input type="password" class="form-control" id="inputPassword" name="password" placeholder="Password">
        </div>
        <div class="checkbox">
            <label>
                <input type="checkbox" name="remember_me" value="1"> Remember login
            </label>
        </div>
        <button type="submit" class="btn btn-info">Login</button>
        {{Form::close()}}
    </div>
</div>
<div class="row">
    <div class="col-lg-4 col-md-4 col-sm-12">
        <p>
            &nbsp;
        </p>
        @if(Config::get('auth.allow_register') === true)
        <p>
            <a href="{{route('register')}}" class="btn btn-default">Register a new account</a>
        </p>
        @endif
        <p>
            <a href="{{route('remindme')}}" class="btn btn-default">Forgot your password?</a>
        </p>

    </div>
</div>

@stop