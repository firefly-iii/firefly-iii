@extends('layout.v3.session')
@section('content')
    <!-- set location data high up -->
    <script type="text/javascript" nonce="{{ $JS_NONCE }}">
        var locations = {!! json_encode($locations) !!};
        var mapboxToken = "{{ config('firefly.mapbox_api_key') }}";
    </script>

    <form action="{{ route('accounts.store') }}" method="post" id="store" class="form-horizontal"
          enctype="multipart/form-data">
        <input type="hidden" name="_token" value="{{ csrf_token() }}"/>
        <input type="hidden" name="objectType" value="{{ $objectType }}"/>
        <input type="hidden" name="active" value="1"/>

        <div class="row">
            <div class="col-lg-6 col-md-6 col-sm-12">
                <div class="card mb-2">
                    <div class="card-header">
                        <h3 class="card-title">{{ __('firefly.mandatoryFields') }}</h3>
                    </div>
                    <div class="card-body">
                        {!! ExpandedForm::text('name') !!}
                        @if('asset' === $objectType || 'liabilities' === $objectType)
                            {!! CurrencyForm::currencyList('currency_id', null, ['helpText' => __('firefly.account_default_currency')]) !!}
                        @endif
                        @if('liabilities' === $objectType)
                            {!! ExpandedForm::select('liability_type_id', $liabilityTypes)  !!}
                            {!! ExpandedForm::amountNoCurrency('opening_balance', null, ['label' => __('firefly.debt_start_amount')]) !!}
                            {!! ExpandedForm::select('liability_direction', $liabilityDirections) !!}
                            {!! ExpandedForm::date('opening_balance_date', null, ['label' => __('firefly.debt_start_date')]) !!}
                            {!! ExpandedForm::percentage('interest') !!}
                            {!! ExpandedForm::select('interest_period', $interestPeriods, null, ['helpText' => __('firefly.interest_period_help')]) !!}
                        @endif
                    </div>
                </div>
            </div>

            <div class="col-lg-6 col-md-6 col-sm-12">
                <div class="card mb-2">
                    <div class="card-header">
                        <h3 class="card-title">{{ __('firefly.optionalFields') }}</h3>
                    </div>
                    <div class="card-body">

                        {!! ExpandedForm::text('iban')  !!}
                        {!! ExpandedForm::text('BIC', null, ['maxlength' => 11])  !!}
                        {!! ExpandedForm::text('account_number') !!}

                        @if('asset' === $objectType)

                            {!! ExpandedForm::amountNoCurrency('opening_balance') !!}
                            {!! ExpandedForm::date('opening_balance_date') !!}
                            {!! ExpandedForm::select('account_role', $roles,null,['helpText' => __('firefly.asset_account_role_help')]) !!}
                            {!! ExpandedForm::amountNoCurrency('virtual_balance') !!}
                        @endif
                        @if($showNetWorth)
                            {!! ExpandedForm::checkbox('include_net_worth', 1) !!}
                        @endif
                        {!! ExpandedForm::textarea('notes',null,['helpText' => trans('firefly.field_supports_markdown')]) !!}
                        {!! ExpandedForm::file('attachments[]', ['multiple' => 'multiple','helpText' => trans('firefly.upload_max_file_size', ['size' => $uploadSize ?? $filesize])]) !!}
                    </div>
                </div>
            </div>
        </div>
        <div class="row">
            <div class="col-lg-6 col-md-6 col-sm-12">
                <div class="card mb-2">
                    <div class="card-header">
                        <h3 class="card-title">{{ __('firefly.options') }}</h3>
                    </div>
                    <div class="card-body">
                        {!! ExpandedForm::optionsList('create','account') !!}
                    </div>
                    <div class="card-footer text-end">
                        <button type="submit" class="btn btn-success">
                            {{ __('firefly.store_new_' . $objectType . '_account') }}
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </form>
@endsection
@section('scripts')
    <script type="text/javascript" nonce="{{ $JS_NONCE }}">
        var iAmOwed = '{{ __('firefly.i_am_owed_amount') }}';
        var iOwe = '{{ __('firefly.i_owe_amount') }}';
    </script>
    <script type="text/javascript" src="v1/js/lib/modernizr-custom.js?v={{ $FF_BUILD_TIME }}"
            nonce="{{ $JS_NONCE }}"></script>
    <script type="text/javascript" src="v1/js/lib/jquery-ui.min.js?v={{ $FF_BUILD_TIME }}" nonce="{{ $JS_NONCE }}"></script>
    <script type="text/javascript" src="v1/js/ff/accounts/create.js?v={{ $FF_BUILD_TIME }}" nonce="{{ $JS_NONCE }}"></script>
@endsection

@section('styles')
    <link href="v1/css/jquery-ui/jquery-ui.structure.min.css?v={{ $FF_BUILD_TIME }}" type="text/css" rel="stylesheet"
          media="all" nonce="{{ $JS_NONCE }}">
    <link href="v1/css/jquery-ui/jquery-ui.theme.min.css?v={{ $FF_BUILD_TIME }}" type="text/css" rel="stylesheet"
          media="all" nonce="{{ $JS_NONCE }}">
@endsection
