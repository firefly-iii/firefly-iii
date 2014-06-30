@extends('layouts.default')
@section('content')
<div class="row">
    <div class="col-lg-8 col-md-8 col-sm-12">
        <h1>Firefly<br/>
            <small>Migration results</small>
        </h1>
        <p class="lead">
            The migration was successful! You can now return to <a href="{{route('index')}}">the home page</a>
            and start using Firefly.
        </p>

        <ul>
            @foreach($messages as $m)
            <li>{{$m}}</li>
            @endforeach
        </ul>
    </div>
</div>

@stop