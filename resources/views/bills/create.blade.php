@extends('layout.v3.session')
@section('content')

    <form method="POST" action="{{ route('subscriptions.store') }}" accept-charset="UTF-8" class="form-horizontal" id="store" enctype="multipart/form-data">
        <input name="_token" type="hidden" value="{{ csrf_token() }}">

        <div class="row">
            <div class="col-lg-6 col-md-6 col-sm-12 col-xs-12">
                <div class="card mb-2">
                    <div class="card-header">
                        <h3 class="card-title">{{ __('firefly.mandatoryFields') }}</h3>
                    </div>
                    <div class="card-body">
                        {!! ExpandedForm::text('name') !!}
                        {!! CurrencyForm::currencyList('transaction_currency_id', $primaryCurrency->id) !!}
                        {!! ExpandedForm::amountNoCurrency('amount_min') !!}
                        {!! ExpandedForm::amountNoCurrency('amount_max') !!}
                        {!! ExpandedForm::date('date',date('Y-m-d')) !!}
                        {!! ExpandedForm::select('repeat_freq',$periods,'monthly') !!}
                        {!! ExpandedForm::integer('skip',0, ['helpText' => trans('firefly.skip_help_text')]) !!}
                    </div>
                </div>

            </div>
            <div class="col-lg-6 col-md-6 col-sm-12 col-xs-12">
                <div class="card mb-2">
                    <div class="card-header">
                        <h3 class="card-title">{{ __('firefly.optionalFields') }}</h3>
                    </div>
                    <div class="card-body">
                        {!! ExpandedForm::date('bill_end_date',null, ['helpText' => trans('firefly.bill_end_date_help')]) !!}
                        {!! ExpandedForm::date('extension_date',null,['helpText' => trans('firefly.bill_extension_date_help')] ) !!}


                        {!! ExpandedForm::textarea('notes',null,['helpText' => trans('firefly.field_supports_markdown')]) !!}
                        {!! ExpandedForm::file('attachments[]', ['multiple' => 'multiple', 'helpText' => trans('firefly.upload_max_file_size', ['size' => print_nice_filesize($uploadSize)])]) !!}
                        {!! ExpandedForm::objectGroup() !!}
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
                        {!! ExpandedForm::optionsList('create','bill') !!}
                    </div>
                    <div class="card-footer text-end">
                        <button type="submit" class="btn btn-success">
                            {{ __('firefly.store_new_bill') }}
                        </button>
                    </div>
                </div>

            </div>
        </div>
    </form>

@endsection

@section('styles')
    <link href="v1/css/bootstrap-tagsinput.css?v={{ $FF_BUILD_TIME }}" type="text/css" rel="stylesheet" media="all" nonce="{{ $JS_NONCE }}">
    <link href="v1/css/jquery-ui/jquery-ui.structure.min.css?v={{ $FF_BUILD_TIME }}" type="text/css" rel="stylesheet" media="all" nonce="{{ $JS_NONCE }}">
    <link href="v1/css/jquery-ui/jquery-ui.theme.min.css?v={{ $FF_BUILD_TIME }}" type="text/css" rel="stylesheet" media="all" nonce="{{ $JS_NONCE }}">
@endsection
@section('scripts')
    @vite(['js/pages/generic.js'])
    <script type="text/javascript" src="v1/js/lib/bootstrap-tagsinput.min.js?v={{ $FF_BUILD_TIME }}" nonce="{{ $JS_NONCE }}"></script>
    <script type="text/javascript" src="v1/js/lib/modernizr-custom.js?v={{ $FF_BUILD_TIME }}" nonce="{{ $JS_NONCE }}"></script>
    <script type="text/javascript" src="v1/js/lib/jquery-ui.min.js?v={{ $FF_BUILD_TIME }}" nonce="{{ $JS_NONCE }}"></script>
    <script type="text/javascript" src="v1/js/ff/bills/create.js?v={{ $FF_BUILD_TIME }}" nonce="{{ $JS_NONCE }}"></script>

    {{-- auto complete for object groups --}}
    <script type="text/javascript" src="v1/js/lib/typeahead/typeahead.bundle.min.js?v={{ $FF_BUILD_TIME }}" nonce="{{ $JS_NONCE }}"></script>
    <script type="text/javascript" src="v1/js/ff/object-groups/create-edit.js?v={{ $FF_BUILD_TIME }}" nonce="{{ $JS_NONCE }}"></script>
@endsection
