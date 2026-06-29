@extends('layout.v3.session')
@section('content')
    <form method="post" action="{{ route('settings.links.update', $linkType->id) }}" class="form-horizontal"
          accept-charset="UTF-8"
          enctype="multipart/form-data">
        <input type="hidden" name="id" value="{{ $linkType->id }}"/>
        <input name="_token" type="hidden" value="{{ csrf_token() }}">
        <div class="row">
            <div class="col-lg-6 col-md-6 col-sm-12 col-xs-12">
                <div class="card mb-2">
                    <div class="card-header">
                        <h3 class="card-title">{{ __('firefly.mandatoryFields') }}</h3>
                    </div>
                    <div class="card-body">
                        {!! ExpandedForm::text('name', $linkType->name, ['helpText' => trans('firefly.link_type_help_name')]) !!}
                        {!! ExpandedForm::text('inward', $linkType->inward, ['helpText' => trans('firefly.link_type_help_inward')]) !!}
                        {!! ExpandedForm::text('outward', $linkType->outward, ['helpText' => trans('firefly.link_type_help_outward')]) !!}
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
                        {!! ExpandedForm::optionsList('update','link_type') !!}
                    </div>
                    <div class="card-footer text-end">
                        <button type="submit" class="btn btn-success">
                            {{ __('firefly.update_link_type') }}
                        </button>
                    </div>
                </div>

            </div>

        </div>
    </form>


@endsection
@section('scripts')
    @vite(['js/pages/generic.js'])
@endsection
