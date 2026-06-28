@extends('layout.v3.session')
@section('content')
    <div class="row">
        <div class="col-lg-6 col-md-12 col-sm-12 col-xs-12">
            <form action="{{ route('settings.notification.post') }}" method="post">
                <input type="hidden" name="_token" value="{{ csrf_token() }}">
                <div class="card mb-2">
                    <div class="card-header">
                        <h3 class="card-title">{{ __('firefly.notification_settings') }}</h3>
                    </div>
                    <div class="card-body">
                        <p>
                            {{ trans('firefly.owner_notifications_expl') }}
                        </p>
                        @foreach($notifications as $notification => $value)
                            <div class="checkbox">
                                <label>
                                    <input value="1" @if(true === $value) checked @endif type="checkbox" name="notification_{{ $notification }}"> {{ trans('firefly.owner_notification_check_' . $notification) }}
                                </label>
                            </div>
                        @endforeach
                        <p class="mt-5">{{ __('firefly.channel_settings') }}</p>
                        {!! ExpandedForm::text('slack_webhook_url', $slackUrl, ['label' => __('firefly.slack_url_label'), 'helpText' => trans('firefly.slack_discord_double')]) !!}

                        {!! ExpandedForm::text('pushover_app_token', $pushoverAppToken) !!}
                        {!! ExpandedForm::text('pushover_user_token', $pushoverUserToken) !!}

                        {!! ExpandedForm::text('ntfy_server', $ntfyServer) !!}
                        {!! ExpandedForm::text('ntfy_topic', $ntfyTopic) !!}
                        {!! ExpandedForm::checkbox('ntfy_auth','1', $ntfyAuth) !!}
                        {!! ExpandedForm::text('ntfy_user', $ntfyUser) !!}
                        {!! ExpandedForm::passwordWithValue('ntfy_pass', $ntfyPass) !!}
                    </div>
                    <div class="card-footer">
                        <button type="submit" class="btn btn-success">
                            <span class="bi bi-check-circle"></span> {{ __('firefly.save_notification_settings') }}
                        </button>
                    </div>
                </div>
            </form>
        </div>
            <div class="col-lg-6 col-md-12 col-sm-12 col-xs-12">
                <form action="{{ route('settings.notification.test') }}" method="post">
                <input type="hidden" name="_token" value="{{ csrf_token() }}">
                <div class="card mb-2">
                    <div class="card-header">
                        <h3 class="card-title">{{ __('firefly.available_channels_title') }}</h3>
                    </div>
                    <div class="card-body">
                        <p>
                            {{ __('firefly.available_channels_expl') }}
                        </p>
                        <ul>
                            @foreach($channels as $name => $info)
                                <li>
                                    @if(true === $info['enabled'] && true === $forcedAvailability[$name])
                                        ☑️ {{ trans('firefly.notification_channel_name_' . $name) }}
                                        @if(0 === $info['ui_configurable'])({{ __('firefly.configure_channel_in_env') }}) @endif
                                    @endif
                                        @if(false === $info['enabled'] or false === $forcedAvailability[$name])
                                        ⚠️ {{ trans('firefly.notification_channel_name_' . $name) }} ({{ __('firefly.channel_not_available') }})
                                    @endif
                                </li>
                            @endforeach
                        </ul>
                    </div>
                    <div class="card-footer">
                        <div class="btn-group">
                            @foreach($channels as $name => $info)
                                @if(true === $info['enabled'] && true === $forcedAvailability[$name])
                                    <button type="submit" name="test_submit" value="{{ $name }}" class="btn btn-outline-secondary">
                                        {{ trans('firefly.test_notification_channel_name_'.$name) }}
                                    </button>
                                @endif
                            @endforeach
                        </div>
                    </div>
                </div>
                </form>
            </div>
    </div>

@endsection
@section('scripts')
    @vite(['js/pages/generic.js'])
@endsection
