@extends('layout.v3.session')


    {{ Breadcrumbs.render(Route.getCurrentRoute.getName) }}
@endsection

@section('content')
    <form method="POST" action="{{ route('rule-groups.store') }}" accept-charset="UTF-8" class="form-horizontal" id="store">
        <input name="_token" type="hidden" value="{{ csrf_token() }}">
        <div class="row">
            <div class="col-lg-6 col-md-6 col-sm-12">
                <div class="card">
                    <div class="card-header">
                        <h3 class="card-title">{{ __('firefly.mandatoryFields') }}</h3>
                    </div>
                    <div class="card-body">
                        {!! ExpandedForm::text('title') }}
                    </div>
                </div>
            </div>
            <div class="col-lg-6 col-md-6 col-sm-12">

                {{-- optional fields --}}
                <div class="card">
                    <div class="card-header">
                        <h3 class="card-title">{{ __('firefly.optionalFields') }}</h3>
                    </div>
                    <div class="card-body">
                        {!! ExpandedForm::textarea('description') }}
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
                        {!! ExpandedForm::optionsList('create','rule-group') }}
                    </div>
                    <div class="card-footer">
                        <button type="submit" class="btn text-end btn-success">{{ 'store_new_rule_group'|_ }}</button>
                    </div>
                </div>

            </div>

        </div>
    </form>
@endsection
@section('scripts')
    <script type="text/javascript" src="v1/js/ff/rule-groups/create.js?v={{ $FF_BUILD_TIME }}" nonce="{{ $JS_NONCE }}"></script>
@endsection
