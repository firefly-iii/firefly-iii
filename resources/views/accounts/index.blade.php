@extends('layouts.default')
@section('content')
{{-- Breadcrumbs::renderIfExists(Route::getCurrentRoute()->getName(), $what) --}}
<div class="row">
    <div class="col-lg-12 col-md-12 col-sm-12">
        <div class="panel panel-default">
            <div class="panel-heading">
                <i class="fa {{{$subTitleIcon}}}"></i> {{{$subTitle}}}

                <!-- ACTIONS MENU -->
                <div class="pull-right">
                    <div class="btn-group">
                        <button type="button" class="btn btn-default btn-xs dropdown-toggle" data-toggle="dropdown">
                            Actions
                            <span class="caret"></span>
                        </button>
                        <ul class="dropdown-menu pull-right" role="menu">
                            <li><a href="{{route('accounts.create',$what)}}"><i class="fa fa-plus fa-fw"></i> New {{$what}} account</a></li>
                        </ul>
                    </div>
                </div>


            </div>
                @include('list.accounts')
        </div>
    </div>
</div>
@stop
@section('scripts')
<script type="text/javascript">
    var what = '{{{$what}}}';
    var currencyCode = '{{Amount::getCurrencyCode()}}';
</script>

<!-- load the libraries and scripts necessary for Google Charts: -->
<script type="text/javascript" src="https://www.google.com/jsapi"></script>
<script type="text/javascript" src="js/gcharts.options.js"></script>
<script type="text/javascript" src="js/gcharts.js"></script>
<script type="text/javascript" src="js/accounts.js"></script>
@stop
