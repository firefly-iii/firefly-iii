@extends('layout.v3.blank')
@section('status_code','503')
@section('status','Service Unavailable')
@section('sub_title', trans('errors.maintenance_mode'))
@section('content')
<div class="row">
    <div class="col-">
        <p>
            {{ trans('errors.be_right_back') }}
        </p>
        <p class="text-danger">
            {{ trans('errors.check_back') }}
        </p>
    </div>
</div>

@endsection

