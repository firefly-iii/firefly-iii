@extends('layouts.default')
@section('content')
<div class="row">
    <div class="col-lg-12 col-sm-12 col-md-12">
        <div class="panel panel-default">
            <div class="panel-heading">
                <i class="fa {{$subTitleIcon}}"></i> {{{$subTitle}}}
            </div>
            <div class="panel-body">
                <div id="transaction-table"></div>
            </div>
        </div>
    </div>
</div>


@stop
@section('scripts')
<script type="text/javascript">
var what = '{{{$what}}}';
</script>
<!-- load the libraries and scripts necessary for Google Charts: -->
<script type="text/javascript" src="https://www.google.com/jsapi"></script>
{{HTML::script('assets/javascript/firefly/gcharts.options.js')}}
{{HTML::script('assets/javascript/firefly/gcharts.js')}}


{{HTML::script('assets/javascript/firefly/transactions.js')}}
@stop