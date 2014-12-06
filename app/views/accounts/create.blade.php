@extends('layouts.default')
@section('content')
{{ Breadcrumbs::renderIfExists(Route::getCurrentRoute()->getName(), $what) }}
{{Form::open(['class' => 'form-horizontal','route' => 'accounts.store'])}}
{{Form::hidden('what',$what)}}
<div class="row">
    <div class="col-lg-6 col-md-6 col-sm-12">
        <div class="panel panel-primary">
            <div class="panel-heading">
                <i class="fa {{{$subTitleIcon}}}"></i> Mandatory fields
            </div>
            <div class="panel-body">
                {{Form::ffText('name')}}
            </div>
        </div>
        <p>
            <button type="submit" class="btn btn-lg btn-success">
                <i class="fa fa-plus-circle"></i> Store new {{{$what}}} account
            </button>
        </p>
    </div>

    <div class="col-lg-6 col-md-6 col-sm-12">


        <div class="panel panel-default">
            <div class="panel-heading">
                <i class="fa fa-smile-o"></i> Optional fields
            </div>
            <div class="panel-body">
            @if($what == 'asset')
                    {{Form::ffBalance('openingbalance')}}
                    {{Form::ffDate('openingbalancedate', date('Y-m-d'))}}
                    @endif
                    {{Form::ffCheckbox('active','1',true)}}
                    {{Form::ffSelect('account_role',Config::get('firefly.accountRoles'))}}
            </div>
        </div>


        <!-- panel for options -->
        <div class="panel panel-default">
            <div class="panel-heading">
                <i class="fa fa-bolt"></i> Options
            </div>
            <div class="panel-body">
                {{Form::ffOptionsList('create','account')}}
            </div>
        </div>

    </div>
</div>

{{Form::close()}}
@stop