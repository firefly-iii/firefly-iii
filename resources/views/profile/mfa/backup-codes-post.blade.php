@extends('layout.v3.session')
@section('content')
        <div class="row">
            <div class="col-lg-6 offset-lg-3 col-md-12 col-sm-12 col-xs-12">
                <div class="card mb-2">
                    <div class="card-header">
                        <h3 class="card-title">{{ __('firefly.mfa_backup_codes_post_title') }}</h3>
                    </div>
                    <div class="card-body">
                        <div class="form group">
                            <p>
                                {{ __('firefly.2fa_backup_codes') }}
                            </p>
                            <textarea rows="10" class="form-control" readonly>{{ $codes }}</textarea>
                        </div>
                    </div>
                    <div class="card-footer">
                        <a class="btn btn-success" href="{{ route('profile.mfa.index') }}">{{ __('firefly.2fa_i_have_them') }}</a>
                    </div>
                </div>
            </div>
        </div>
@endsection
@section('scripts')
    @vite(['js/pages/generic.js'])
@endsection
