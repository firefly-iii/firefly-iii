@extends('layouts.default')
@section('content')
{{ Breadcrumbs::renderIfExists(Route::getCurrentRoute()->getName()) }}
<div class="row">
    <div class="col-lg-6 col-md-12 col-sm-12">
        <div class="panel panel-primary">
            <div class="panel-heading">
                Change your password
            </div>
            <div class="panel-body">
                {{Form::open(['class' => 'form-horizontal','id' => 'change-password'])}}
                    <div class="form-group">
                        <label for="inputOldPassword" class="col-sm-4 control-label">Old password</label>
                        <div class="col-sm-8">
                            <input type="password" class="form-control" id="inputOldPassword" placeholder="Old password" name="old">
                        </div>
                    </div>

                    <div class="form-group">
                        <label for="inputNewPassword1" class="col-sm-4 control-label">New password</label>
                        <div class="col-sm-8">
                            <input type="password" class="form-control" id="inputNewPassword1" placeholder="New password" name="new1">
                        </div>
                    </div>

                    <div class="form-group">
                        <label for="inputNewPassword2" class="col-sm-4 control-label">New password (again)</label>
                        <div class="col-sm-8">
                            <input type="password" class="form-control" id="inputNewPassword2" placeholder="New password (again)" name="new2">
                        </div>
                    </div>
                    <div class="form-group">
                        <div class="col-sm-offset-4 col-sm-10">
                            <button type="submit" class="btn btn-success">Change your password</button>
                        </div>
                    </div>
                {{Form::close()}}
            </div>
        </div>
    </div>
</div>
@stop
@section('scripts')
@stop