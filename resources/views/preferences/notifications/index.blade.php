@extends('layout.v3.session')
@section('content')
    <div x-data="index">
    <div class="row mt-1">
        {{-- layout settings column A --}}
        <div class="col-lg-6 col-md-6 col-sm-12 col-xs-12">
            <div class="card mb-2">
                <div class="card-header">
                    <h3 class="card-title">TODO list of channels.</h3>
                </div>
                <div class="card-body">
                    <p>
                        {{ __('firefly.available_channels_expl') }}
                    </p>
                    <template x-if="true === loadingChannels">
                        <div class="text-center">
                            <div class="spinner-border spinner-border-sm" role="status">
                                <span class="visually-hidden">{{ __('firefly.thinking') }}</span>
                            </div>
                        </div>
                    </template>
                    <template x-if="false === loadingChannels">
                    <ul>
                        <template x-for="(channel, name) in channels" :key="name">">
                            <li>
                                <template x-if="channel.enabled">
                                    <span>☑️ </span>
                                </template>
                                <template x-if="!channel.enabled">
                                    <span>⚠️ </span>
                                </template>
                                <span x-text="i18next.t('firefly.notification_channel_name_' + name)"></span>
                                <template x-if="0 === channel.ui_configurable">
                                    <span>({{ __('firefly.configure_channel_in_env') }})</span>
                                </template>
                                <template x-if="!channel.enabled">
                                    <span>({{ __('firefly.channel_not_available') }})</span>
                                </template>
                                </li>
                        </template>
                    </ul>
                    </template>
                </div>
                <div class="card-footer">
                    <template x-if="false === loadingChannels">
                        <template x-for="(channel, name) in channels" :key="name">">
                            <template x-if="channel.enabled">
                                <span x-text="name"></span>
                            </template>
                        </template>
                    </template>
                </div>
            </div>
            <div class="card mb-2">
                <div class="card-header">
                    <h3 class="card-title">TODO list of notifications.</h3>
                </div>
                <div class="card-body">

                    <p>{{ __('firefly.pref_notifications_help') }}</p>
                    {{--
                    @foreach($notifications as $id => $info)
                        <div class="form-group">
                            <div class="col-sm-10">
                                <div class="checkbox">
                                    <label>
                                        @if($info['configurable'])
                                            <input type="checkbox" name="notification_{{ $id }}"
                                                   {{ $info['enabled'] === true ? 'checked' : '' }} value="1">
                                        @else
                                            <input readonly disabled type="checkbox" checked
                                                   value="1">
                                        @endif
                                        {{ trans('firefly.pref_notification_' . $id) }}
                                    </label>
                                </div>
                            </div>
                        </div>
                    @endforeach
                    --}}
                </div>
            </div>
        </div>
        <div class="col-lg-6 col-md-6 col-sm-12 col-xs-12">
            <div class="card mb-2">
                <div class="card-header">
                    <h3 class="card-title">{{ __('firefly.pref_notifications_settings') }}</h3>
                </div>
                <div class="card-body">
                    <p>{{ __('firefly.pref_notifications_settings_help') }}</p>
                    {{--
                    {!! ExpandedForm::text('slack_webhook_url', $slackUrl, ['label' => 'slack_url_label', 'helpText' => 'slack_discord_double']) !!}

                    {!! ExpandedForm::text('pushover_app_token', $pushoverAppToken) !!}
                    {!! ExpandedForm::text('pushover_user_token', $pushoverUserToken) !!}
                    --}}
                    <p>
                        {{ __('firefly.pref_notifications_settings_help') }}
                    </p>
                    <div class="btn-group">
                        {{--
                        @foreach($channels as $name => $info)
                            @if(true === $info['enabled'] && true === $forcedAvailability[$name])
                                <a href="#" data-channel="{{ $name }}"
                                   class="btn btn-outline-secondary submit-test">
                                    {{ trans('firefly.test_notification_channel_name_'.$name) }}
                                </a>
                            @endif
                        @endforeach
                        --}}
                    </div>
                </div>
            </div>
        </div>
    </div>
    </div>
@endsection

@section('scripts')
    @vite(['js/pages/preferences/notifications.js'])
@endsection
