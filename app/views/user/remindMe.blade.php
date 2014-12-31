@extends('layouts.guest')
@section('content')
<div class="row">
    <div class="col-md-4 col-md-offset-4">
        <div class="login-panel panel panel-default">
            <div class="panel-heading">
                <h3 class="panel-title">Firefly III &mdash; Reset your password</h3>
            </div>
            <div class="panel-body">
                {{Form::open(['id' => 'remindMe'])}}
                <div class="form-group">
                    <label for="inputEmail">Email address</label>
                    <input type="email" class="form-control" id="inputEmail" name="email" placeholder="Enter email">
                </div>
                <p>
                    <button type="submit" class="btn btn-success btn-lg btn-block">Submit</button>
                </p>
                {{Form::close()}}
                <div class="btn-group btn-group-justified btn-group-sm">
                    <a href="{{route('login')}}" class="btn btn-default">Back to the login form</a>
                    <a href="{{route('register')}}" class="btn btn-default">Register a new account</a>
                </div>
                </div>
            </div>
        </div>
    </div>
@stop