@extends('layouts.default')
@section('content')
{{ Breadcrumbs::renderIfExists(Route::getCurrentRoute()->getName()) }}
    <div class="row">
    <div class="col-lg-6 col-md-12 col-sm-12">
        <div class="panel panel-primary">
            <div class="panel-heading">
                Options
            </div>
            <div class="panel-body">
                <a href="{{route('change-password')}}">Change your password</a>
            </div>
        </div>
    </div>
</div>
@stop
@section('scripts')
@stop
