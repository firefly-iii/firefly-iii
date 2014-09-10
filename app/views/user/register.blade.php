@extends('layouts.guest')
@section('content')
<div class="row">
    <div class="col-md-4 col-md-offset-4">
        <div class="login-panel panel panel-default">
            <div class="panel-heading">
                <h3 class="panel-title">Firefly III &mdash; Register</h3>
            </div>
            <div class="panel-body">
                <p>
                    Registering an account on Firefly requires an e-mail address.
                    All instructions will be sent to you.
                </p>
                {{Form::open()}}
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
                    <a href="{{route('remindme')}}" class="btn btn-default">Forgot your password?</a>
                </div>

                </div>
            </div>
        </div>
    </div>
@stop