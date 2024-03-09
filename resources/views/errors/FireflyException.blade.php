@extends('layout.v2.error')
@section('content')
    <!--
    <title>Firefly III Exception :(</title>



-->


    <div class="row">
        <div class="col">
            <h1><a href="{{ route('index') }}"><strong>Firefly</strong> III</a></h1>
        </div>
    </div>
    <div class="row">
        <div class="col">
            <h3 class="text-danger">{{ trans('errors.error_occurred') }}</h3>
        </div>
    </div>

    <div class="row">
        <div class="col-lg-10 col-lg-offset-1 col-md-12 col-sm-12 col-xs-12">
            <p>
                {{ trans('errors.error_not_recoverable') }}
            </p>
            <p class="text-danger">
                {{ $exception->getMessage() ?? 'General unknown error' }}
            </p>
            <p>
                {!! trans('errors.error_location', ['file' => $exception->getFile(), 'line' =>  $exception->getLine(), 'code' => $exception->getCode() ]) !!}
            </p>
        </div>
    </div>

    @if(!$debug)
    <div class="row">
        <div class="col-lg-10 col-lg-offset-1 col-md-12 col-sm-12 col-xs-12">
            <h4>
                {{ trans('errors.more_info') }}
            </h4>
            <p>
                {!! trans('errors.collect_info')  !!}
                {!! trans('errors.collect_info_more')  !!}
            </p>
            <h4>
                {{ trans('errors.github_help') }}
            </h4>
            <p>
                {{ trans('errors.github_instructions') }} |raw
            </p>
            <ol>
                <li>{{ trans('errors.use_search') }}</li>
                <li>{{ trans('errors.include_info', ['link' => route('debug') ]) }}</li> |raw
                <li>{{ trans('errors.tell_more') }}</li>
                <li>{{ trans('errors.include_logs') }}</li>
                <li>{{ trans('errors.what_did_you_do') }}</li>
            </ol>
        </div>
    </div>
    @endif
    @if($debug)
        <div class="row">
            <div class="col-lg-10 col-lg-offset-1 col-md-12 col-sm-12 col-xs-12">
                <h4>{{ trans('errors.error') }}</h4>
                <p>
                    {!! trans('errors.error_location', ['file' => $exception->getFile(), 'line' =>  $exception->getLine(), 'code' => $exception->getCode() ]) !!}
                </p>
                <h4>
                    {{ trans('errors.stacktrace') }}
                </h4>
                <div style="font-family: monospace;font-size:11px;">
                    {!!  nl2br($exception->getTraceAsString())  !!}
                </div>
            </div>
        </div>
    @endif

@endsection


