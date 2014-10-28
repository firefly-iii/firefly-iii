@extends('layouts.default')
@section('content')
<div class="row">
    <div class="col-lg-12 col-md-12 col-sm-12">
        <p class="lead">
            Bla bla expense
        </p>
        <p class="text-info">
            Bla bla bla expense
        </p>
    </div>
</div>
<div class="row">
    <div class="col-lg-12 col-md-12 col-sm-12">
        <p>
            <a href="{{route('accounts.create','expense')}}" class="btn btn-success">Create a new expense account</a>
        </p>
        @if(count($accounts) > 0)
            @include('accounts.list')
        <p>
            <a href="{{route('accounts.create','expense')}}" class="btn btn-success">Create a new expense account</a>
        </p>
        @endif
    </div><!-- TODO remove me -->

</div>

@stop