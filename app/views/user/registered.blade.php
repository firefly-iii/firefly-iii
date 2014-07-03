@extends('layouts.guest')
@section('content')
<div class="row">
    <div class="col-lg-12 col-md-12 col-sm-12">
        <h1>Firefly<br/>
            <small>Password sent!</small>
        </h1>
    </div>
</div>

<div class="row">
    <div class="col-lg-12 col-md-12 col-sm-12">
        <p>
            You're about to get an e-mail. Use the contents to <a href="{{route('login')}}">log in</a>.
        </p>
    </div>
</div>

@stop