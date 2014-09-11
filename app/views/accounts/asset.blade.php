@extends('layouts.default')
@section('content')
<div class="row">
    <div class="col-lg-12 col-md-12 col-sm-12">
        <p class="lead">
            Accounts are your personal accounts that represent value.
        </p>
        <p class="text-info">
            "Asset accounts" are your personal accounts that represent value. For example: bank accounts, saving
            accounts, stock, etc.
        </p>
    </div>
</div>
<div class="row">
    <div class="col-lg-12 col-md-12 col-sm-12">
        <p>
            <a href="{{route('accounts.create','asset')}}" class="btn btn-success">Create a new asset account</a>
        </p>
        @if(count($accounts) > 0)
            @include('accounts.list')
        <p>
            <a href="{{route('accounts.create','asset')}}" class="btn btn-success">Create a new asset account</a>
        </p>
        @endif
    </div>

</div>

@stop