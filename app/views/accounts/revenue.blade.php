@extends('layouts.default')
@section('content')
<div class="row">
    <div class="col-lg-12 col-md-12 col-sm-12">
        <p class="lead">
            Bla bla revenue
        </p>
        <p class="text-info">
            Bla bla bla revenue
        </p>
    </div>
</div>
<div class="row">
    <div class="col-lg-12 col-md-12 col-sm-12">
        <p>
            <a href="{{route('accounts.create','revenue')}}" class="btn btn-success">Create a new revenue account</a>
        </p>
        @if(count($accounts) > 0)
            @include('accounts.list')
        <p>
            <a href="{{route('accounts.create','revenue')}}" class="btn btn-success">Create a new revenue account</a>
        </p>
        @endif
    </div>

</div>
<!-- TODO remove me -->
@stop