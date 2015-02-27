@extends('layouts.default')
@section('content')
{{ Breadcrumbs::renderIfExists(Route::getCurrentRoute()->getName()) }}
<div class="row">
    <div class="col-lg-4 col-md-4 col-sm-12 ">
        <!-- time based navigation -->
        @include('partials.date_nav')
    </div>
</div>
<div class="row">
    <div class="col-lg-12 col-md-12 col-sm-12">
        <div class="panel panel-default">
            <div class="panel-heading">
                {{{$subTitle}}}
            </div>
            <div class="panel-body">
                @include('list.journals-full',['journals' => $list])
            </div>
        </div>
    </div>
</div>


@stop
