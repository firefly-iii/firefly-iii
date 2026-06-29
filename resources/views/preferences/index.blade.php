@extends('layout.v3.session')
@section('content')
    <form method="POST" action="{{ route('preferences.update') }}" accept-charset="UTF-8" class="form-horizontal"
          id="preferences">
        <input name="_token" type="hidden" value="{{ csrf_token() }}">

        @if($errors->any())
            <div class="alert alert-danger" role="alert">
                <ul>
                    @foreach($errors->getBags() as $bag)
                        @foreach($bag->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    @endforeach
                </ul>
            </div>
        @endif

        <div class="row">
            <div class="col-lg-12 col-md-12 col-sm-12 col-xs-12">
                {{-- start of preferences tabs --}}
                <ul class="nav nav-tabs" id="preferencesTab" role="tablist">
                    <li class="nav-item" role="presentation">
                        <button class="nav-link active" id="general-tab" data-bs-toggle="tab"
                                data-bs-target="#general-tab-pane" type="button" role="tab"
                                aria-controls="general-tab-pane"
                                aria-selected="true">{{ __('firefly.preferences_general') }}</button>
                    </li>
                    <li class="nav-item" role="presentation">
                        <button class="nav-link" id="frontpage-tab" data-bs-toggle="tab"
                                data-bs-target="#frontpage-tab-pane" type="button" role="tab"
                                aria-controls="frontpage-tab-pane"
                                aria-selected="false">{{ __('firefly.preferences_frontpage') }}</button>
                    </li>
                    <li class="nav-item" role="presentation">
                        <button class="nav-link" id="layout-tab" data-bs-toggle="tab" data-bs-target="#layout-tab-pane"
                                type="button" role="tab" aria-controls="layout-tab-pane"
                                aria-selected="false">{{ __('firefly.preferences_layout') }}</button>
                    </li>
                    <li class="nav-item" role="presentation">
                        <button class="nav-link" id="notifications-tab" data-bs-toggle="tab"
                                data-bs-target="#notifications-tab-pane" type="button" role="tab"
                                aria-controls="notifications-tab-pane"
                                aria-selected="false">{{ __('firefly.preferences_notifications') }}</button>
                    </li>
                </ul>
                <div class="tab-content" id="preferencesTabContent">
                    <div class="tab-pane fade show active" id="general-tab-pane" role="tabpanel" aria-labelledby="general-tab" tabindex="0">
                        {{-- general settings here --}}
                        <div class="row">
                            {{-- general settings column A --}}
                            <div class="col-lg-6 col-md-6 col-sm-12 col-xs-12 pt-1">
                                {{-- language --}}
                                <div class="card mb-2">
                                    <div class="card-header">
                                        <h3 class="card-title">{{ __('firefly.pref_languages') }}</h3>
                                    </div>
                                    <div class="card-body">
                                        <p>{{ __('firefly.pref_languages_help') }}</p>
                                        <div class="form-group">
                                            <div class="col-sm-12">
                                                <select class="form-control" id="lang_holder" name="language">
                                                    @foreach($languages as $key => $lang)
                                                        <option @if($language === $key)selected @endif
                                                        value="{{ $key }}">{{ $lang['name_locale'] }}
                                                            ({{ $lang['name_english'] }})
                                                        </option>
                                                    @endforeach
                                                </select>
                                            </div>
                                        </div>
                                        <p>
                                            <br/>
                                            {{ __('firefly.pref_languages_locale') }}
                                        </p>
                                    </div>
                                </div>

                                {{-- locale --}}
                                @if(!$isDocker)
                                    <div class="card mb-2">
                                        <div class="card-header">
                                            <h3 class="card-title">{{ __('firefly.pref_locale') }}</h3>
                                        </div>
                                        <div class="card-body">
                                            <p>{{ __('firefly.pref_locale_help') }}</p>
                                            <p class="text-warning">{{ __('firefly.pref_locale_exception') }}</p>
                                            <div class="form-group">
                                                <div class="col-sm-12">
                                                    <select class="form-control" id="locale_holder" name="locale">
                                                        @foreach($locales as $key => $loc)
                                                            <option @if($locale === $key) selected @endif
                                                            value="{{ $key }}">
                                                                @if('equal' === $key)
                                                                    {{ __('firefly.equal_to_language') }}
                                                                @else
                                                                    {{ $loc }}
                                                                @endif</option>

                                                        @endforeach
                                                    </select>
                                                </div>
                                            </div>
                                            <ul class="text-warning">
                                                @if($IS_DEMO_SITE)
                                                    <li class="text-danger">{{ __('firefly.pref_locale_no_demo') }}</li>
                                                @endif
                                            </ul>
                                        </div>
                                    </div>
                                @else
                                    <input type="hidden" name="locale" value="equal"/>
                                @endif

                                {{-- fiscal year --}}
                                <div class="card mb-2">
                                    <div class="card-header">
                                        <h3 class="card-title">{{ __('firefly.pref_custom_fiscal_year') }}</h3>
                                    </div>
                                    <div class="card-body">
                                        <p>
                                            {{ __('firefly.pref_custom_fiscal_year_help') }}
                                        </p>
                                        @php
                                            $isCustomFiscalYear = $customFiscalYear === 1;
                                        @endphp
                                        {!! ExpandedForm::checkbox('customFiscalYear','1',1 === $customFiscalYear,['label' => 'pref_custom_fiscal_year_label' ]) !!}
                                        {!! ExpandedForm::date('fiscalYearStart',1 === $customFiscalYear,['label' => 'pref_fiscal_year_start_label' ]) !!}
                                    </div>
                                </div>

                                {{--  conversion back to primary currency --}}
                                @if(\FireflyIII\Support\Facades\AppConfiguration::get('enable_exchange_rates', true))
                                    <div class="card mb-2">
                                        <div class="card-header">
                                            <h3 class="card-title">{{ __('firefly.pref_convert_to_primary') }}</h3>
                                        </div>
                                        <div class="card-body">
                                            <p>
                                                {{ __('firefly.pref_convert_to_primary_help') }}
                                            </p>
                                            {!! ExpandedForm::checkbox('convertToPrimary','1', $convertToPrimary,['label' => 'pref_convert_primary_help']) !!}
                                        </div>
                                    </div>
                                @endif
                                {{--  conversion back to primary currency --}}
                                <div class="card mb-2">
                                    <div class="card-header">
                                        <h3 class="card-title">{{ __('firefly.pref_anonymous') }}</h3>
                                    </div>
                                    <div class="card-body">
                                        <p>
                                            {{ __('firefly.pref_anonymous_help') }}
                                        </p>
                                        {!! ExpandedForm::checkbox('anonymous','1',$anonymous,[ 'label' => __('firefly.pref_anonymous_label')]) !!}
                                    </div>
                                </div>
                            </div>


                            {{-- general settings column B --}}
                            <div class="col-lg-6 col-md-6 col-sm-12 col-xs-12 pt-1">
                                {{-- transaction preferences --}}
                                <div class="card mb-2">
                                    <div class="card-header">
                                        <h3 class="card-title">{{ __('firefly.pref_optional_fields_transaction') }}</h3>
                                    </div>
                                    <div class="card-body">
                                        <p>
                                            {{ __('firefly.pref_optional_fields_transaction_help') }}
                                        </p>
                                        <h4>{{ __('firefly.optional_tj_date_fields') }}</h4>
                                        {!! ExpandedForm::checkbox('tj[interest_date]','1', $tjOptionalFields['interest_date'],['label' => 'pref_optional_tj_interest_date']) !!}
                                        {!! ExpandedForm::checkbox('tj[book_date]','1', $tjOptionalFields['book_date'],['label' => 'pref_optional_tj_book_date']) !!}
                                        {!! ExpandedForm::checkbox('tj[process_date]','1', $tjOptionalFields['process_date'],['label' => 'pref_optional_tj_process_date']) !!}
                                        {!! ExpandedForm::checkbox('tj[due_date]','1', $tjOptionalFields['due_date'],['label' => 'pref_optional_tj_due_date']) !!}
                                        {!! ExpandedForm::checkbox('tj[payment_date]','1', $tjOptionalFields['payment_date'],['label' => 'pref_optional_tj_payment_date']) !!}
                                        {!! ExpandedForm::checkbox('tj[invoice_date]','1', $tjOptionalFields['invoice_date'],['label' => 'pref_optional_tj_invoice_date']) !!}

                                        <h4>{{ __('firefly.optional_tj_other_fields') }}</h4>
                                        {!! ExpandedForm::checkbox('tj[internal_reference]','1', $tjOptionalFields['internal_reference'],['label' => 'pref_optional_tj_internal_reference']) !!}
                                        {!! ExpandedForm::checkbox('tj[external_url]','1', $tjOptionalFields['external_url'],['label' => 'pref_optional_tj_external_url']) !!}
                                        {!! ExpandedForm::checkbox('tj[notes]','1', $tjOptionalFields['notes'],['label' => 'pref_optional_tj_notes']) !!}
                                        {!! ExpandedForm::checkbox('tj[location]','1', $tjOptionalFields['location'],['label' => 'pref_optional_tj_location']) !!}
                                        {!! ExpandedForm::checkbox('tj[links]','1', $tjOptionalFields['links'],['label' => 'pref_optional_tj_links']) !!}

                                        <h4>{{ __('firefly.optional_tj_attachment_fields') }}</h4>
                                        {!! ExpandedForm::checkbox('tj[attachments]','1', $tjOptionalFields['attachments'], ['label' => 'pref_optional_tj_attachments']) !!}
                                    </div>

                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="tab-pane fade" id="frontpage-tab-pane" role="tabpanel" aria-labelledby="frontpage-tab" tabindex="0">
                        {{-- frontpage settings here --}}
                        <div class="row">
                            {{-- frontpage settings column a --}}
                            <div class="col-lg-6 col-md-6 col-sm-12 col-xs-12 mt-1">
                                <div class="card mb-2">
                                    <div class="card-header">
                                        <h3 class="card-title">{{ __('firefly.pref_home_screen_accounts') }}</h3>
                                    </div>
                                    <div class="card-body">
                                        <p>{{ __('firefly.pref_home_screen_accounts_help') }}</p>
                                        @foreach($groupedAccounts as $type => $accounts)
                                            <strong>{{ $type }}</strong>
                                            @foreach($accounts as $id => $name)
                                                <div class="form-group">
                                                    <div class="col-sm-10">
                                                        <div class="checkbox">
                                                            <label>
                                                                @if(in_array($id, $frontpageAccounts) || 0 === count($frontpageAccounts))
                                                                    <input type="checkbox" name="frontpageAccounts[]"
                                                                           value="{{ $id }}"
                                                                           checked> {{ $name }}
                                                                @else
                                                                    <input type="checkbox" name="frontpageAccounts[]"
                                                                           value="{{ $id }}"> {{ $name }}
                                                                @endif
                                                            </label>
                                                        </div>
                                                    </div>
                                                </div>
                                            @endforeach
                                        @endforeach
                                    </div>
                                </div>
                            </div>
                            {{-- frontpage settings column b (empty) --}}
                            <div class="col-lg-6 col-md-6 col-sm-12 col-xs-12">
                            </div>
                        </div>
                    </div>
                    <div class="tab-pane fade" id="layout-tab-pane" role="tabpanel" aria-labelledby="layout-tab"
                         tabindex="0">
                        {{-- layout settings here --}}
                        <div class="row">
                            {{-- layout settings column A --}}
                            <div class="col-lg-6 col-md-6 col-sm-12 col-xs-12 mt-1">
                                {{-- view range --}}
                                <div class="card mb-2">
                                    <div class="card-header">
                                        <h3 class="card-title">{{ __('firefly.pref_view_range') }}</h3>
                                    </div>
                                    <div class="card-body">
                                        <p>{{ __('firefly.pref_view_range_help') }}</p>

                                        <div class="radio">
                                            <label>
                                                <input type="radio" name="viewRange"
                                                       value="1D" @if($viewRange === '1D') checked @endif>
                                                {{ __('firefly.pref_1D') }}
                                            </label>
                                        </div>

                                        <div class="radio">
                                            <label>
                                                <input type="radio" name="viewRange"
                                                       value="1W" @if($viewRange === '1W') checked @endif>
                                                {{ __('firefly.pref_1W') }}
                                            </label>
                                        </div>

                                        <div class="radio">
                                            <label>
                                                <input type="radio" name="viewRange"
                                                       value="last7" @if($viewRange === 'last7') checked @endif>
                                                {{ __('firefly.pref_last7') }}
                                            </label>
                                        </div>

                                        <div class="radio">
                                            <label>
                                                <input type="radio" name="viewRange"
                                                       value="1M" @if($viewRange === '1M') checked @endif>
                                                {{ __('firefly.pref_1M') }}
                                            </label>
                                        </div>
                                        <div class="radio">
                                            <label>
                                                <input type="radio" name="viewRange"
                                                       value="last30" @if($viewRange === 'last30') checked @endif>
                                                {{ __('firefly.pref_last30') }}
                                            </label>
                                        </div>
                                        <div class="radio">
                                            <label>
                                                <input type="radio" name="viewRange"
                                                       value="MTD" @if($viewRange === 'MTD') checked @endif>
                                                {{ __('firefly.pref_MTD') }}
                                            </label>
                                        </div>

                                        <div class="radio">
                                            <label>
                                                <input type="radio" name="viewRange"
                                                       value="3M" @if($viewRange === '3M') checked @endif>
                                                {{ __('firefly.pref_3M') }}
                                            </label>
                                        </div>
                                        <div class="radio">
                                            <label>
                                                <input type="radio" name="viewRange"
                                                       value="last90" @if($viewRange === 'last90') checked @endif>
                                                {{ __('firefly.pref_last90') }}
                                            </label>
                                        </div>
                                        <div class="radio">
                                            <label>
                                                <input type="radio" name="viewRange"
                                                       value="QTD" @if($viewRange === 'QTD') checked @endif>
                                                {{ __('firefly.pref_QTD') }}
                                            </label>
                                        </div>

                                        <div class="radio">
                                            <label>
                                                <input type="radio" name="viewRange"
                                                       value="6M" @if($viewRange === '6M') checked @endif>
                                                {{ __('firefly.pref_6M') }}
                                            </label>
                                        </div>
                                        <div class="radio">
                                            <label>
                                                <input type="radio" name="viewRange"
                                                       value="1Y" @if($viewRange === '1Y') checked @endif>
                                                {{ __('firefly.pref_1Y') }}
                                            </label>
                                        </div>
                                        <div class="radio">
                                            <label>
                                                <input type="radio" name="viewRange"
                                                       value="YTD" @if($viewRange === 'YTD') checked @endif>
                                                {{ __('firefly.pref_YTD') }}
                                            </label>
                                        </div>
                                        <div class="radio">
                                            <label>
                                                <input type="radio" name="viewRange"
                                                       value="last365" @if($viewRange === 'last365') checked @endif>
                                                {{ __('firefly.pref_last365') }}
                                            </label>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            {{-- layout settings column B --}}
                            <div class="col-lg-6 col-md-6 col-sm-12 col-xs-12 mt-1">
                                <div class="card mb-2">
                                    <div class="card-header">
                                        <h3 class="card-title">{{ __('firefly.list_page_size_title') }}</h3>
                                    </div>
                                    <div class="card-body">
                                        <p>{{ __('firefly.list_page_size_help') }}</p>
                                        {!! ExpandedForm::integer('listPageSize',$listPageSize,['label' => __('firefly.list_page_size_label')]) !!}
                                    </div>
                                </div>
                                <div class="card mb-2">
                                    <div class="card-header">
                                        <h3 class="card-title">{{ __('firefly.dark_mode_preference') }}</h3>
                                    </div>
                                    <div class="card-body">
                                        <p>{{ __('firefly.dark_mode_preference_help') }}</p>
                                        @foreach($availableDarkModes as $mode)
                                            <div class="radio">
                                                <label>
                                                    <input type="radio" name="darkMode"
                                                           value="{{ $mode }}"
                                                           @if($darkMode === $mode) checked @endif>
                                                    {{ __('dark_mode_option_' . $mode) }}
                                                </label>
                                            </div>
                                        @endforeach
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="tab-pane fade" id="notifications-tab-pane" role="tabpanel"
                         aria-labelledby="notifications-tab" tabindex="0">
                        {{-- layout settings here --}}
                        <div class="row mt-1">
                            {{-- layout settings column A --}}
                            <div class="col-lg-6 col-md-6 col-sm-12 col-xs-12">
                                {{-- view range --}}
                                <div class="card mb-2">
                                    <div class="card-header">
                                        <h3 class="card-title">{{ __('firefly.pref_notifications') }}</h3>
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
                                                        @if(0 === $info['ui_configurable'])
                                                            ({{ __('firefly.configure_channel_in_env') }})
                                                        @endif
                                                    @endif
                                                    @if(false === $info['enabled'] || false === $forcedAvailability[$name])
                                                        ⚠️ {{ trans('firefly.notification_channel_name_' . $name) }}
                                                        ({{ __('firefly.channel_not_available') }})
                                                    @endif
                                                </li>
                                            @endforeach
                                        </ul>
                                        <p>{{ __('firefly.pref_notifications_help') }}</p>
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
                                        {!! ExpandedForm::text('slack_webhook_url', $slackUrl, ['label' => 'slack_url_label', 'helpText' => 'slack_discord_double']) !!}

                                        {!! ExpandedForm::text('pushover_app_token', $pushoverAppToken) !!}
                                        {!! ExpandedForm::text('pushover_user_token', $pushoverUserToken) !!}

                                        {!! ExpandedForm::hidden('ntfy_server', $ntfyServer) !!}
                                        {!! ExpandedForm::hidden('ntfy_topic', $ntfyTopic) !!}
                                        {!! ExpandedForm::hidden('ntfy_auth', $ntfyAuth) !!}
                                        {!! ExpandedForm::hidden('ntfy_user', $ntfyUser) !!}
                                        {!! ExpandedForm::hidden('ntfy_pass', $ntfyPass) !!}
                                        <p>
                                            {{ __('firefly.pref_notifications_settings_help') }}
                                        </p>
                                        <div class="btn-group">
                                            @foreach($channels as $name => $info)
                                                @if(true === $info['enabled'] && true === $forcedAvailability[$name])
                                                    <a href="#" data-channel="{{ $name }}"
                                                       class="btn btn-outline-secondary submit-test">
                                                        {{ trans('firefly.test_notification_channel_name_'.$name) }}
                                                    </a>
                                                @endif
                                            @endforeach
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="row">
            <div class="col-lg-12 col-md-12 col-sm-12">
                <div class="form-group">
                    <div class="col-sm-12">
                        <button type="submit" name="form_submit" value="form_submit" class="btn btn-success btn-lg">{{ __('firefly.pref_save_settings') }}</button>
                    </div>
                </div>
            </div>
        </div>
    </form>
@endsection
@section('scripts')
    @vite(['js/pages/generic.js'])

    <script type="text/javascript" nonce="{{ $JS_NONCE }}">
        var postUrl = "{{ route('preferences.test-notification') }}";

        $(document).ready(function () {
            $('button[data-bs-toggle="tab"]').on('show.bs.tab', function (e) {
                localStorage.setItem('preferencesActiveTab', $(e.target).attr('data-bs-target'));
            });
            var activeTab = localStorage.getItem('preferencesActiveTab');
            if (activeTab) {
                $('#preferencesTab button[data-bs-target="' + activeTab + '"]').click();
            }
        });
    </script>
    <script type="text/javascript" src="v1/js/lib/modernizr-custom.js?v={{ $FF_BUILD_TIME }}"
            nonce="{{ $JS_NONCE }}"></script>
    <script type="text/javascript" src="v1/js/lib/jquery-ui.min.js?v={{ $FF_BUILD_TIME }}"
            nonce="{{ $JS_NONCE }}"></script>
    <script type="text/javascript" src="v1/js/ff/preferences/index.js?v={{ $FF_BUILD_TIME }}"
            nonce="{{ $JS_NONCE }}"></script>
@endsection

@section('styles')
    <link href="v1/css/jquery-ui/jquery-ui.structure.min.css?v={{ $FF_BUILD_TIME }}" type="text/css" rel="stylesheet"
          media="all" nonce="{{ $JS_NONCE }}">
    <link href="v1/css/jquery-ui/jquery-ui.theme.min.css?v={{ $FF_BUILD_TIME }}" type="text/css" rel="stylesheet"
          media="all" nonce="{{ $JS_NONCE }}">
@endsection
