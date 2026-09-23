@extends('layout.v3.blank')
@section('status_code','405')
@section('status','Method Not Allowed')
@section('sub_title', trans('errors.405_header'))
@section('content')
<div class="row">
    <div class="col">
        @if(str_starts_with($exception->getMessage(),'Webhooks'))
            <p class="lead">
                {{ $exception->getMessage() }}
            </p>
        @endif
        <p>
            {{ trans('errors.405_page_does_not_exist') }}
        </p>
        <p>
            {{ trans('errors.404_send_error') }}
        </p>
        <p>
            {!!  trans('errors.405_github_link')  !!}
        </p>
    </div>
</div>
@endsection
