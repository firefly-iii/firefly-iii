@extends('layout.v3.session')
@section('content')

<!--

Security
- single user mode

Features
- enable exchange rates
- use running balance

External connections
- enable external map
- enable external exchange rates
- allow webhooks

Technical stuff
- valid url protocols
- is demo site

-->


    <form action="{{ route('settings.configuration.index.post') }}" method="post" id="store" class="form-horizontal">
        <input type="hidden" name="_token" value="{{ csrf_token() }}"/>
        {{--  security --}}
        <div class="row">
            <div class="col-lg-8 offset-lg-2 col-xl-8 offset-xl-2 col-md-12 col-sm-12 col-xs-12">
                <div class="card mb-2">
                    <div class="card-header">
                        <h3 class="card-title">{{ __('firefly.config_security') }}</h3>
                    </div>
                    <div class="card-body">
                        <h4>
                            {{ __('firefly.setting_single_user_mode') }}
                        </h4>
                        <p>
                            {{ __('firefly.setting_single_user_mode_explain') }}
                        </p>
                        {!! ExpandedForm::checkbox('single_user_mode','1', $singleUserMode) !!}
                    </div>
                </div>
            </div>
        </div>

        <div class="row">
            <div class="col-lg-8 offset-lg-2 col-xl-8 offset-xl-2 col-md-12 col-sm-12 col-xs-12">
                <div class="card mb-2">
                    <div class="card-header">
                        <h3 class="card-title">{{ __('firefly.config_features') }}</h3>
                    </div>
                    <div class="card-body">
                        <h4>
                            {{ __('firefly.setting_enable_exchange_rates') }}
                        </h4>
                        <p>
                            {{ __('firefly.setting_enable_exchange_rates_explain') }}
                        </p>
                        {!! ExpandedForm::checkbox('enable_exchange_rates','1', $enableExchangeRates) !!}

                        <h4>
                            {{ __('firefly.setting_use_running_balance') }}
                        </h4>
                        <p>
                            {{ __('firefly.setting_use_running_balance_explain') }}
                        </p>
                        {!! ExpandedForm::checkbox('use_running_balance','1', $useRunningBalance) !!}

                        <h4>
                            {{ __('firefly.setting_enable_batch_processing') }}
                        </h4>
                        <p>
                            {{ __('firefly.setting_enable_batch_processing_explain') }}
                        </p>
                        {!! ExpandedForm::checkbox('enable_batch_processing','1', $enableBatchProcessing) !!}
                    </div>
                </div>
            </div>
        </div>

        <div class="row">
            <div class="col-lg-8 offset-lg-2 col-xl-8 offset-xl-2 col-md-12 col-sm-12 col-xs-12">
                <div class="card mb-2">
                    <div class="card-header">
                        <h3 class="card-title">{{ __('firefly.config_external_connections') }}</h3>
                    </div>
                    <div class="card-body">
                        <h4>
                            {{ __('firefly.setting_enable_external_map') }}
                        </h4>
                        <p>
                            {{ __('firefly.setting_enable_external_map_explain') }}
                        </p>
                        {!! ExpandedForm::checkbox('enable_external_map','1', $enableExternalMap) !!}

                        <h4>
                            {{ __('firefly.setting_enable_external_rates') }}
                        </h4>
                        <p>
                            {{ __('firefly.setting_enable_external_rates_explain') }}
                        </p>
                        {!! ExpandedForm::checkbox('enable_external_rates','1', $enableExternalRates) !!}

                        <h4>
                            {{ __('firefly.setting_allow_webhooks') }}
                        </h4>
                        <p>
                            {{ __('firefly.setting_allow_webhooks_explain') }}
                        </p>
                        {!! ExpandedForm::checkbox('allow_webhooks','1', $allowWebhooks) !!}
                    </div>
                </div>
            </div>
        </div>

        <div class="row">
            <div class="col-lg-8 offset-lg-2 col-xl-8 offset-xl-2 col-md-12 col-sm-12 col-xs-12">
                <div class="card mb-2">
                    <div class="card-header">
                        <h3 class="card-title">{{ __('firefly.config_tech') }}</h3>
                    </div>
                    <div class="card-body">
                        <h4>
                            {{ __('firefly.setting_valid_url_protocols') }}
                        </h4>
                        <p>
                            {{ __('firefly.setting_valid_url_protocols_explain') }}
                        </p>
                        {!! ExpandedForm::text('valid_url_protocols', $validUrlProtocols) !!}

                        <h4>
                            {{ __('firefly.setting_is_demo_site') }}
                        </h4>
                        <p>
                            {{ __('firefly.setting_is_demo_site_explain') }}
                        </p>
                        {!! ExpandedForm::checkbox('is_demo_site','1', $isDemoSite)  !!}
                        <p>
                            <button type="submit" class="btn btn-success">
                                {{ __('firefly.store_configuration') }}
                            </button>
                        </p>
                    </div>
                </div>
            </div>
        </div>
    </form>

@endsection
@section('scripts')
    @vite(['js/pages/generic.js'])
@endsection
