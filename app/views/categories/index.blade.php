@extends('layouts.default')
@section('content')
<div class="row">
    <div class="col-lg-12 col-md-12 col-sm-12">
        <div class="panel panel-default">
            <div class="panel-heading">
                <i class="fa {{{$mainTitleIcon}}}"></i> Categories

                <!-- ACTIONS MENU -->
                <div class="pull-right">
                    <div class="btn-group">
                        <button type="button" class="btn btn-default btn-xs dropdown-toggle" data-toggle="dropdown">
                            Actions
                            <span class="caret"></span>
                        </button>
                        <ul class="dropdown-menu pull-right" role="menu">
                            <li><a href="{{route('categories.create')}}"><i class="fa fa-plus fa-fw"></i> New category</a></li>
                        </ul>
                    </div>
                </div>


            </div>
            <div class="panel-body">
                @include('list.categories')
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
{{HTML::script('assets/javascript/firefly/categories.js')}}
@stop