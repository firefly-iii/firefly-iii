@extends('layouts.default')
@section('content')

<div class="row">
    <div class="col-lg-9 col-md-9 col-sm-7">
        <div class="panel panel-default">
            <div class="panel-heading">
                Overview
            </div>
            <div class="panel-body">
                <div id="componentOverview"></div>
            </div>
        </div>

         <div class="panel panel-default">
            <div class="panel-heading">
                Transactions
            </div>
            <div class="panel-body">
                <div id="transactions"></div>
            </div>
        </div>
    </div>
    <div class="col-lg-3 col-md-3 col-sm-5">
        BLa bla something here.
    </div>
</div>

@stop
@section('scripts')
<script type="text/javascript">
    var componentID = {{$category->id}};
    var year = {{Session::get('start')->format('Y')}};

</script>

<!-- load the libraries and scripts necessary for Google Charts: -->
<script type="text/javascript" src="https://www.google.com/jsapi"></script>
{{HTML::script('assets/javascript/firefly/gcharts.options.js')}}
{{HTML::script('assets/javascript/firefly/gcharts.js')}}
{{HTML::script('assets/javascript/firefly/categories.js')}}

@stop