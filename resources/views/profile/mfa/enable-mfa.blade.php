@extends('layout.v3.session')
@section('content')
    <form method="POST" action="{{ route('profile.mfa.enableMFA.post') }}" accept-charset="UTF-8" class="form-horizontal" id="preferences_code">
        <input name="_token" type="hidden" value="{{ csrf_token() }}">
        <div class="row">
            <div class="col-lg-6 offset-lg-3 col-md-12 col-sm-12 col-xs-12">
                <div class="card mb-2">
                    <div class="card-header">
                        <h3 class="card-title">{{ __('firefly.pref_two_factor_auth_code') }}</h3>
                    </div>
                    <div class="card-body">
                        <p class="hidden-print">
                            {{ __('firefly.pref_two_factor_auth_code_help') }}
                        </p>
                        <div class="form group">
                            <div class="col-sm-8 col-md-offset-4 hidden-print">
                                <img src="{{ $image }}" alt="{{ __('firefly.pref_two_factor_qr_code') }}">
                            </div>
                            <p class="hidden-print">
                                {!! trans('firefly.2fa_use_secret_instead', ['secret' => $secret]) !!}
                            </p>
                            <p class="hidden-print text-danger">
                                {{ __('firefly.mfa_warning_code_changes') }}
                            </p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="row">
            <div class="col-lg-6 offset-lg-3 col-md-12 col-sm-12 col-xs-12">
                <div class="card mb-2">
                    <div class="card-body">
                        {!! ExpandedForm::password('password', ['helpText' => __('firefly.current_password_confirm_mfa')]) !!}
                        {!! ExpandedForm::text('code', $code ?? '') !!}
                    </div>
                    <div class="card-footer text-end">
                        <button type="submit" class="btn btn-success btn-lg">{{ __('firefly.pref_save_settings') }}</button>
                    </div>
                </div>
            </div>
        </div>
    </form>
@endsection
@section('scripts')
    @vite(['js/pages/generic.js'])

    <script type="text/javascript" nonce="{{ $JS_NONCE }}">
        $(function () {
            "use strict";

            // Focus first visible form element.
            $("form#preferences_code input:enabled:visible:first").first().select();
        });
    </script>
@endsection
