@extends('layouts.default')
@section('content')
{!! Breadcrumbs::renderIfExists(Route::getCurrentRoute()->getName()) !!}
<div class="row">
    <div class="col-lg-6 col-md-12 col-sm-12">
        <div class="panel panel-red">
            <div class="panel-heading">
                Delete your account
            </div>
            <div class="panel-body">

                <p class="text-danger">
                    Deleting your account will also delete any accounts, transactions, <em>anything</em>
                    you might have saved into Firefly III. It'll be GONE.
                </p>
                <p class="text-danger">
                    Enter your password to continue.
                </p>

                @if($errors->count() > 0)
                    <ul>
                        @foreach($errors->all() as $err)
                            <li class="text-info">{{$err}}</li>
                        @endforeach
                    </ul>

                @endif

                {!! Form::open(['class' => 'form-horizontal','id' => 'change-password']) !!}
                    <div class="form-group">
                        <label for="password" class="col-sm-4 control-label">Password</label>
                        <div class="col-sm-8">
                            <input type="password" class="form-control" id="password" placeholder="Password" name="password">
                        </div>
                    </div>

                    <div class="form-group">
                        <div class="col-sm-offset-4 col-sm-10">
                            <button type="submit" onclick="confirm('Are you sure? You cannot undo this.')" class="btn btn-danger">DELETE your account</button>
                        </div>
                    </div>
                {!! Form::close() !!}
            </div>
        </div>
    </div>
</div>
@stop
@section('scripts')
@stop
