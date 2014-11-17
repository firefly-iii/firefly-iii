@extends('layouts.default')
@section('content')
<div class="row">
 <div class="col-lg-6 col-md-6 col-sm-12">
    <div class="panel panel-default">
        <div class="panel-heading">
            Something
        </div>
        <div class="panel-body">
            {{$reminder->data->text}}
        </div>
    </div>
 </div>
</div>
@stop