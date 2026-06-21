@extends('layout.v3.session')
@section('content')
    <div class="row">
        <div class="col-lg-6 col-md-12 col-sm-12 col-xs-12">
            <div class="card mb-2">
                <div class="card-header">
                    <h3 class="card-title">{{ __('firefly.instance_configuration') }}</h3>
                </div>
                <div class="card-body">
                    <ul>
                        <li>
                            <a href="{{ route('settings.configuration.index') }}">{{ __('firefly.firefly_instance_configuration') }}</a>
                        </li>
                        <li><a href="{{ route('settings.links.index') }}">{{ __('firefly.journal_link_configuration') }}</a></li>
                        <li><a href="{{ route('settings.update-check') }}">{{ __('firefly.update_check_title') }}</a></li>
                        <li><a href="{{ route('settings.notification.index') }}">{{ __('firefly.settings_notifications') }}</a></li>
                    </ul>
                </div>
            </div>
            <div class="card mb-2">
                <div class="card-header">
                    <h3 class="card-title">{{ __('firefly.user_administration') }}</h3>
                </div>
                <div class="card-body">
                    <ul>
                        <li><a href="{{ route('settings.users') }}">{{ __('firefly.list_all_users') }}</a></li>
                    </ul>
                </div>
            </div>
        </div>
        <div class="col-lg-6 col-md-12 col-sm-12 col-xs-12">
            <div class="card mb-2">
                <div class="card-header">
                    <h3 class="card-title">{{ __('firefly.admin_maintanance_title') }}</h3>
                </div>
                <div class="card-body">
                    <p>
                        {{ __('firefly.admin_maintanance_expl') }}
                    </p>
                    <p>
                        <a href="{{ route('flush') }}"
                           class="btn btn-warning">{{ __('firefly.admin_maintenance_clear_cache') }}</a>
                    </p>
                </div>
            </div>
        </div>
    </div>
@endsection

