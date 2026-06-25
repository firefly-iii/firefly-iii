@extends('layout.v3.session')


    {{ Breadcrumbs.render(Route.getCurrentRoute.getName, tag) }}
@endsection

@section('content')
    <!-- set location data high up -->
    <script type="text/javascript" nonce="{{ $JS_NONCE }}">
        var locations = {{ locations|json_encode|raw }};
        var mapboxToken = "{{ config('firefly.mapbox_api_key') }}";
    </script>


    <form method="post" action="{{ route('tags.update',tag.id) }}" class="form-horizontal" accept-charset="UTF-8"
          enctype="multipart/form-data">
        <input type="hidden" name="_token" value="{{ csrf_token() }}"/>
        <input type="hidden" name="id" value="{{ tag.id }}"/>

        <div class="row">
            <div class="col-lg-5 col-md-5 col-sm-12">
                <div class="card">
                    <div class="card-header">
                        <h3 class="card-title">{{ __('firefly.mandatoryFields') }}</h3>
                    </div>
                    <div class="card-body">
                        {!! ExpandedForm::text('tag', tag.tag) }}
                    </div>
                </div>
            </div>

            <div class="col-lg-7 col-md-7 col-sm-12">

                <div class="card">
                    <div class="card-header">
                        <h3 class="card-title">{{ __('firefly.optionalFields') }}</h3>
                    </div>
                    <div class="card-body">
                        {!! ExpandedForm::date('date', tag.date.format('Y-m-d')) }}
                        {!! ExpandedForm::textarea('description', tag.description) }}
                        {!! ExpandedForm::file('attachments[]', ['multiple' => 'multiple','helpText' => trans('firefly.upload_max_file_size', ['size' => print_nice_filesize($uploadSize)])]) }}
                    </div>
                </div>
            </div>
        </div>
        <div class="row">
            <div class="col-lg-6 col-md-6 col-sm-12 col-xs-12">
                {{-- panel for options --}}
                <div class="card">
                    <div class="card-header">
                        <h3 class="card-title">{{ __('firefly.options') }}</h3>
                    </div>
                    <div class="card-body">
                        {!! ExpandedForm::optionsList('update','tag') }}
                    </div>
                    <div class="card-footer">
                        <button type="submit" class="btn text-end btn-success">
                            {{ 'update_tag'|_ }}
                        </button>
                    </div>
                </div>

            </div>
        </div>

    </form>

@endsection
@section('scripts')
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
