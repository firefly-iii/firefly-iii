@extends('layout.v2.error')
@section('status_code','500')
@section('status','Internal Server Error')
@section('sub_title', trans('errors.error_occurred'))
@section('content')
    <div class="row">
        <div class="col">
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
    @if(!($debug ?? false))
        <div class="row">
            <div class="col">
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
                    {!! trans('errors.github_instructions') !!}
                </p>
                <ol>
                    <li>{{ trans('errors.use_search') }}</li>
                    <li>{!!  trans('errors.include_info', ['link' => route('debug') ]) !!}</li>
                    <li>{{ trans('errors.tell_more') }}</li>
                    <li>{{ trans('errors.include_logs') }}</li>
                    <li>{{ trans('errors.what_did_you_do') }}</li>
                </ol>
            </div>
        </div>
    @endif
    @if($debug ?? false)
        <div class="row">
            <div class="col">
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


