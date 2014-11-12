@extends('layouts.default')
@section('content')
<div class="row">
    <div class="col-lg-12 col-sm-12 col-md-12">
    <div class="panel panel-default">
        <div class="panel-heading">
            <i class="fa {{$mainTitleIcon}}"></i> {{{$title}}}
        </div>
        <div class="panel-body">
            <div id="recurring-table"></div>
        </div>
        </div>
    </div>
</div>
@stop
@section('scripts')

<!-- load the libraries and scripts necessary for Google Charts: -->
<script type="text/javascript" src="https://www.google.com/jsapi"></script>
{{HTML::script('assets/javascript/firefly/gcharts.options.js')}}
{{HTML::script('assets/javascript/firefly/gcharts.js')}}


<script src="assets/javascript/firefly/accounts.js"></script>

{{HTML::script('assets/javascript/firefly/recurring.js')}}
@stop