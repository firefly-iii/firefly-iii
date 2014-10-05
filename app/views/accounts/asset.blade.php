@extends('layouts.default')
@section('content')
<div class="row">
    <div class="col-lg-12 col-md-12 col-sm-12">
        <p>
            <a href="{{route('accounts.create','asset')}}" class="btn btn-success btn-large">Create a new asset account</a>
        </p>
        @if(count($accounts) > 0)
            @include('accounts.list')
        <p>
            <a href="{{route('accounts.create','asset')}}" class="btn btn-success btn-large">Create a new asset account</a>
        </p>
        @endif
    </div>

</div>

@stop