@extends('layout.v3.session')
@section('content')
    <!-- set location data high up -->
    <script type="text/javascript" nonce="{{ $JS_NONCE }}">
        var locations = {{ json_encode($locations) }};
        var mapboxToken = "{{ config('firefly.mapbox_api_key') }}";
    </script>

    <form method="post" action="{{ route('accounts.update',$account->id) }}" class="form-horizontal"
          accept-charset="UTF-8"
          enctype="multipart/form-data">
        <input type="hidden" name="_token" value="{{ csrf_token() }}"/>

        <input type="hidden" name="id" value="{{ $account->id }}"/>
        <input type="hidden" name="$objectType" value="{{ $objectType }}"/>

        <div class="row">
            <div class="col-lg-6 col-md-6 col-sm-12 col-xs-12">
                <div class="card">
                    <div class="card-header">
                        <h3 class="card-title">{{ __('firefly.mandatoryFields') }}</h3>
                    </div>
                    <div class="card-body">
                        {!! ExpandedForm::text('name', $account->name) !!}
                        @if($canEditCurrency && (in_array($account->accountType->type, ['Default account','Asset account'],true) || 'liabilities' === $objectType))
                            {!! CurrencyForm::currencyList('currency_id', null, ['helpText' => __('firefly.account_default_currency')]) !!}
                        @endif
                        @if(!$canEditCurrency && (in_array($account->accountType->type, ['Default account','Asset account'],true) || 'liabilities' === $objectType))
                            <input type="hidden" name="currency_id" value="{{ $currency->id }}"/>
                            {!! ExpandedForm::staticText('currency_id', trans('firefly.account_locked_currency', ['name' => $currency->name])) !!}
                        @endif

                        @if('liabilities' === $objectType)
                            {!! ExpandedForm::select('liability_type_id', $liabilityTypes) !!}
                            {!! ExpandedForm::amountNoCurrency('opening_balance', null, ['label' => __('firefly.debt_start_amount')]) !!}
                            {!! ExpandedForm::select('liability_direction', $liabilityDirections) !!}
                            {!! ExpandedForm::date('opening_balance_date', $preFilled['opening_balance_date'], ['label' => __('firefly.debt_start_date')]) !!}
                            {!! ExpandedForm::percentage('interest') !!}
                            {!! ExpandedForm::select('interest_period', $interestPeriods) !!}
                        @endif
                    </div>
                </div>

            </div>
            <div class="col-lg-6 col-md-6 col-sm-12 col-xs-12">
                <div class="card">
                    <div class="card-header">
                        <h3 class="card-title">{{ __('firefly.optionalFields') }}</h3>
                    </div>
                    <div class="card-body">
                        {!! ExpandedForm::text('iban', $account->iban) !!}
                        {!! ExpandedForm::text('BIC', null, ['maxlength' => 11]) !!}
                        @if('ccAsset' === $preFilled['account_role'])
                            {!! ExpandedForm::text('account_number', null , ['label' => trans('form.creditCardNumber')]) !!}
                        @else
                            {!! ExpandedForm::text('account_number') !!}
                        @endif

                        @if(in_array($account->accountType->type, ['Default account','Asset account'],true))
                            {!! ExpandedForm::amountNoCurrency('opening_balance',null) !!}
                            {!! ExpandedForm::date('opening_balance_date', $preFilled['opening_balance_date']) !!}
                            {!! ExpandedForm::select('account_role', $roles) !!}
                            {!! ExpandedForm::amountNoCurrency('virtual_balance',null) !!}
                        @endif
                        @if($showNetWorth)
                            {!! ExpandedForm::checkbox('include_net_worth', 1) !!}
                        @endif
                        {!! ExpandedForm::textarea('notes', $preFilled['notes'],['helpText' => trans('firefly.field_supports_markdown')]) !!}
                        {!! ExpandedForm::checkbox('active', 1) !!}
                        {!! ExpandedForm::file('attachments[]', ['multiple' => 'multiple','helpText' => trans('firefly.upload_max_file_size', ['size' => $uploadSize ?? $filesize])]) !!}

                    </div>
                </div>

                @if('ccAsset' === $preFilled['account_role'])
                    <div class="card">
                        <div class="card-header">
                            <h3 class="card-title">{{ __('firefly.credit_card_options') }}</h3>
                        </div>
                        <div class="card-body">
                            {!! ExpandedForm::select('cc_type',Config::get('firefly.ccTypes')) !!}
                            {!! ExpandedForm::date('cc_monthly_payment_date',null,['helpText' => trans('firefly.cc_monthly_payment_date_help')]) !!}
                        </div>
                    </div>
                @endif
            </div>
        </div>
        <div class="row">
            <div class="col-lg-6 col-md-6 col-sm-12">
                <div class="card">
                    <div class="card-header">
                        <h3 class="card-title">{{ __('firefly.options') }}</h3>
                    </div>
                    <div class="card-body">
                        {!! ExpandedForm::optionsList('update','account') !!}
                    </div>
                    <div class="card-footer text-end">
                        <button type="submit" class="btn btn-success">
                            {{ __('firefly.update_' . $objectType . '_account') }}
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
    <script type="text/javascript" src="v1/js/ff/accounts/edit.js?v={{ $FF_BUILD_TIME }}" nonce="{{ $JS_NONCE }}"></script>
@endsection

@section('styles')
    <link href="v1/css/jquery-ui/jquery-ui.structure.min.css?v={{ $FF_BUILD_TIME }}" type="text/css" rel="stylesheet"
          media="all" nonce="{{ $JS_NONCE }}">
    <link href="v1/css/jquery-ui/jquery-ui.theme.min.css?v={{ $FF_BUILD_TIME }}" type="text/css" rel="stylesheet"
          media="all" nonce="{{ $JS_NONCE }}">
@endsection
