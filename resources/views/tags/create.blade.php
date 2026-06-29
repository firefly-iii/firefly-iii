@extends('layout.v3.session')
@section('content')
    <!-- set location data high up -->
    <script type="text/javascript" nonce="{{ $JS_NONCE }}">
        var locations = {!! json_encode($locations) !!};
        var mapboxToken = "{{ config('firefly.mapbox_api_key') }}";
    </script>
    <form method="POST" action="{{ route('tags.store') }}" accept-charset="UTF-8" class="form-horizontal" id="store"
          enctype="multipart/form-data">
        <input name="_token" type="hidden" value="{{ csrf_token() }}">
        <div class="row">
            <div class="col-lg-5 col-md-5 col-sm-12">
                <div class="card mb-2">
                    <div class="card-header">
                        <h3 class="card-title">{{ __('firefly.mandatoryFields') }}</h3>
                    </div>
                    <div class="card-body">
                        {!! ExpandedForm::text('tag') !!}
                    </div>
                </div>
            </div>

            <div class="col-lg-7 col-md-7 col-sm-12">

                <div class="card mb-2">
                    <div class="card-header">
                        <h3 class="card-title">{{ __('firefly.optionalFields') }}</h3>
                    </div>
                    <div class="card-body">
                        {!! ExpandedForm::date('date') !!}
                        {!! ExpandedForm::textarea('description') !!}
                        {!! ExpandedForm::file('attachments[]', ['multiple' => 'multiple','helpText' => trans('firefly.upload_max_file_size', ['size' => print_nice_filesize($uploadSize)])]) !!}
                    </div>
                </div>
            </div>
        </div>
        <div class="row">
            <div class="col-lg-6 col-md-6 col-sm-12 col-xs-12">
                {{-- panel for options --}}
                <div class="card mb-2">
                    <div class="card-header">
                        <h3 class="card-title">{{ __('firefly.options') }}</h3>
                    </div>
                    <div class="card-body">
                        {!! ExpandedForm::optionsList('create','tag') !!}
                    </div>
                    <div class="card-footer text-end">
                        <button type="submit" class="btn btn-success">
                            {{ __('firefly.store_new_tag') }}
                        </button>
                    </div>
                </div>

            </div>
        </div>
    </form>
@endsection
@section('scripts')
    @vite(['js/pages/generic.js'])
    <script type="text/javascript" src="v1/js/lib/modernizr-custom.js?v={{ $FF_BUILD_TIME }}"
            nonce="{{ $JS_NONCE }}"></script>
    <script type="text/javascript" src="v1/js/lib/jquery-ui.min.js?v={{ $FF_BUILD_TIME }}" nonce="{{ $JS_NONCE }}"></script>
    <script src="v1/js/ff/tags/create-edit.js?v={{ $FF_BUILD_TIME }}" nonce="{{ $JS_NONCE }}"></script>
@endsection
@section('styles')
    <link href="v1/css/jquery-ui/jquery-ui.structure.min.css?v={{ $FF_BUILD_TIME }}" type="text/css" rel="stylesheet"
          media="all" nonce="{{ $JS_NONCE }}">
    <link href="v1/css/jquery-ui/jquery-ui.theme.min.css?v={{ $FF_BUILD_TIME }}" type="text/css" rel="stylesheet"
          media="all" nonce="{{ $JS_NONCE }}">
@endsection
