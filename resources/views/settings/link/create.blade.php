@extends('layout.v3.session')


    {{ Breadcrumbs.render(Route.getCurrentRoute.getName) }}
@endsection

@section('content')

    <form method="POST" action="{{ route('settings.links.store') }}" accept-charset="UTF-8" class="form-horizontal">
        <input name="_token" type="hidden" value="{{ csrf_token() }}">
        <div class="row">
            <div class="col-lg-6 col-md-6 col-sm-12 col-xs-12">
                <div class="card">
                    <div class="card-header">
                        <h3 class="card-title">{{ __('firefly.mandatoryFields') }}</h3>
                    </div>
                    <div class="card-body">
                        {!! ExpandedForm::text('name', null, {helpText: trans('firefly.link_type_help_name')}) }}
                        {!! ExpandedForm::text('inward', null, {helpText: trans('firefly.link_type_help_inward')}) }}
                        {!! ExpandedForm::text('outward', null, {helpText: trans('firefly.link_type_help_outward')}) }}
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
                        {!! ExpandedForm::optionsList('create','link_type') }}
                    </div>
                    <div class="card-footer">
                        <button type="submit" class="btn text-end btn-success">{{ 'store_new_link_type'|_ }}</button>
                    </div>
                </div>

            </div>

        </div>
    </form>


@endsection
