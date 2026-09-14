@extends('layout.v3.session')
@section('content')
    <form method="POST" action="{{ route('profile.mfa.disableMFA.post') }}" accept-charset="UTF-8" class="form-horizontal">
        <input name="_token" type="hidden" value="{{ csrf_token() }}">
        <div class="row">
            <div class="col-lg-6 offset-lg-3 col-md-12 col-sm-12 col-xs-12">
                <div class="card mb-2">
                    <div class="card-header">
                        <h3 class="card-title">{{ __('firefly.disable_mfa_page') }}</h3>
                    </div>
                    <div class="card-body">
                        <p class="hidden-print">
                            {!! __('firefly.disable_mfa_intro')  !!}
                        </p>
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
                    <div class="card-footer">
                        <button type="submit" class="btn btn-danger">{{__('firefly.pref_disable_mfa') }}</button>
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
