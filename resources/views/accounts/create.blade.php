@extends('layouts.default')
@section('content')
{!! Breadcrumbs::renderIfExists(Route::getCurrentRoute()->getName(), $what) !!}
{!! Form::open(['class' => 'form-horizontal','id' => 'store','route' => 'accounts.store']) !!}
{!! Form::hidden('what',$what) !!}

@foreach ($errors->all() as $error)
    <p class="error">{{ $error }}</p>
@endforeach

<div class="row">
    <div class="col-lg-6 col-md-6 col-sm-12">
        <div class="panel panel-primary">
            <div class="panel-heading">
                <i class="fa {{{$subTitleIcon}}}"></i> Mandatory fields
            </div>
            <div class="panel-body">
                {!! ExpandedForm::text('name') !!}
            </div>
        </div>
    </div>

    <div class="col-lg-6 col-md-6 col-sm-12">

        @if($what == 'asset')
        <div class="panel panel-default">
            <div class="panel-heading">
                <i class="fa fa-smile-o"></i> Optional fields
            </div>
            <div class="panel-body">

                    {!! ExpandedForm::balance('openingBalance') !!}
                    {!! ExpandedForm::date('openingBalanceDate', date('Y-m-d')) !!}
                    {!! ExpandedForm::select('accountRole',Config::get('firefly.accountRoles'),null,['helpText' => 'Any extra options resulting from your choice can be set later.']) !!}
                    {!! ExpandedForm::balance('virtualBalance') !!}

            </div>
        </div>
        @endif

        <!-- panel for options -->
        <div class="panel panel-default">
            <div class="panel-heading">
                <i class="fa fa-bolt"></i> Options
            </div>
            <div class="panel-body">
                {!! ExpandedForm::optionsList('create','account') !!}
            </div>
        </div>

    </div>
</div>
<div class="row">
    <div class="col-lg-12 col-md-12 col-sm-12 col-xs-12">
        <p>
            <button type="submit" class="btn btn-lg btn-success">
                <i class="fa fa-plus-circle"></i> Store new {{{$what}}} account
            </button>
        </p>
    </div>
</div>

</form>
@stop
