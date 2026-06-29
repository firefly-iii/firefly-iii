@extends('layout.v3.session')
@section('content')
    <div class="row">
        <div class="col-lg-8 offset-lg-2 col-md-12 col-sm-12">
            <div class="card mb-2">
                <div class="card-header">
                    <h3 class="card-title">
                        {{ __('firefly.mfa_index_title') }}
                    </h3>
                </div>
                <div class="card-body">
                    <p>
                        @if(true === $enabledMFA)
                            {{ __('firefly.mfa_index_enabled') }}
                        @endif
                        @if(false === $enabledMFA)
                            {{ __('firefly.mfa_index_disabled') }}
                        @endif

                    </p>
                    <p>
                        {{ __('firefly.mfa_index_intro') }}
                    </p>
                    <p>
                        {{ __('firefly.mfa_index_owner') }}
                    </p>
                    @if(true === $enabledMFA)
                        <div class="btn-group">
                            <a href="{{ route('profile.mfa.disableMFA') }}" class="btn btn-danger"><em class="fa fa-unlock-alt"></em> {{ __('firefly.pref_two_factor_auth_disable_2fa') }}</a>
                            <a href="{{ route('profile.mfa.backup-codes') }}" class="btn btn-outline-secondary"><em class="bi bi-calculator"></em> {{ __('firefly.pref_two_factor_new_backup_codes') }}</a>
                        </div>
                    @endif
                    @if(false === $enabledMFA)
                        <p>
                            <a class="btn btn-info" href="{{ route('profile.mfa.enableMFA') }}"><em class="bi bi-calculator"></em> {{ __('firefly.pref_enable_two_factor_auth') }}</a>
                        </p>
                    @endif
                </div>
            </div>
        </div>
    </div>
@endsection
@section('scripts')
    @vite(['js/pages/generic.js'])
@endsection
