@extends('layouts.guest')
@section('content')
<div class="row">
    <div class="col-md-4 col-md-offset-4">
        <div class="login-panel panel panel-default">
            <div class="panel-heading">
                <h3 class="panel-title">Firefly III &mdash; Sign In</h3>
            </div>
            <div class="panel-body">



        {{Form::open(['id' => 'login'])}}
        <div class="form-group">
            <input type="email" class="form-control" id="inputEmail" name="email" placeholder="E-mail">
        </div>
        <div class="form-group">
            <input type="password" class="form-control" id="inputPassword" name="password" placeholder="Password">
        </div>
        <div class="checkbox">
            <label>
                <input type="checkbox" name="remember_me" value="1"> Remember me
            </label>
        </div>
        <p>
            <button type="submit" class="btn btn-lg btn-success btn-block">Login</button>
        </p>
        <div class="btn-group btn-group-justified btn-group-sm">
            @if(Config::get('auth.allow_register') === true)
                <a href="{{route('register')}}" class="btn btn-default">Register</a>
            @endif
            <a href="{{route('remindme')}}" class="btn btn-default">Forgot your password?</a>
        </div>
        {{Form::close()}}
    </div>
</div>
</div>
</div>
@stop