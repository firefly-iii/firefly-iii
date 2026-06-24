@extends('layout.v3.session')
@section('content')

    <form method="post" action="{{ route('categories.update',$category->id) }}" class="form-horizontal"
          accept-charset="UTF-8"
          enctype="multipart/form-data">
        <input type="hidden" name="_token" value="{{ csrf_token() }}"/>

        <input type="hidden" name="id" value="{{ $category->id }}"/>
        <div class="row">
            <div class="col-lg-6 col-md-6 col-sm-12">
                <div class="card">
                    <div class="card-header">
                        <h3 class="card-title">{{ __('firefly.mandatoryFields') }}</h3>
                    </div>
                    <div class="card-body">
                        {!! ExpandedForm::text('name', $category->name) !!}
                    </div>
                </div>
            </div>
            <div class="col-lg-6 col-md-6 col-sm-12 col-xs-12">
                {{-- panel for optional fields --}}
                <div class="card">
                    <div class="card-header">
                        <h3 class="card-title">{{ __('firefly.optionalFields') }}</h3>
                    </div>
                    <div class="card-body">
                        {!! ExpandedForm::textarea('notes', $preFilled['notes'], ['helpText' => trans('firefly.field_supports_markdown')] ) !!}
                        {!! ExpandedForm::file('attachments[]', ['multiple' => 'multiple','helpText' => trans('firefly.upload_max_file_size', ['size' => print_nice_filesize($uploadSize)])]) !!}
                    </div>
                </div>
            </div>
        </div>
        <div class="row">
            <div class="col-lg-6 col-md-6 col-sm-12">


                {{-- panel for options --}}
                <div class="card">
                    <div class="card-header">
                        <h3 class="card-title">{{ __('firefly.options') }}</h3>
                    </div>
                    <div class="card-body">
                        {!! ExpandedForm::optionsList('update','category') !!}
                    </div>
                    <div class="card-footer">
                        <button type="submit" class="btn text-end btn-success">
                            {{ __('firefly.update_category') }}
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </form>
@endsection
@section('scripts')
    <script type="text/javascript" src="v1/js/ff/categories/edit.js?v={{ $FF_BUILD_TIME }}" nonce="{{ $JS_NONCE }}"></script>
@endsection
