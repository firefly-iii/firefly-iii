@extends('layout.v3.session')
@section('content')
<form method="POST" action="{{ route('profile.mfa.backup-codes.post') }}" accept-charset="UTF-8" class="form-horizontal" id="preferences_code">
    <input name="_token" type="hidden" value="{{ csrf_token() }}">
        <div class="row">
            <div class="col-lg-6 offset-lg-3 col-md-12 col-sm-12 col-xs-12">
                <div class="card mb-2">
                    <div class="card-header">
                        <h3 class="card-title">{{ __('firefly.mfa_backup_codes_title') }}</h3>
                    </div>
                    <div class="card-body">
                        <div class="form group">
                            <p>
                                {{ __('firefly.mfa_backup_codes_intro') }}
                            </p>
                            <p class="text-danger">
                                {{ __('firefly.mfa_backup_codes_quick') }}
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
                <div class="card-footer">
                    <button type="submit" class="btn btn-success">{{ __('firefly.pref_save_settings') }}</button>
                </div>
            </div>
        </div>
    </div>


</form>
@endsection
@section('scripts')
    @vite(['js/pages/generic.js'])
@endsection
