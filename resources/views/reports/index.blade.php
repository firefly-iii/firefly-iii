@extends('layout.v3.session')
@section('content')
    <div class="row">
            <div class="col-lg-6 col-md-6 col-sm-12 col-xs-12">
                <form class="nodisablebutton" id="report-form" action="{{ route('reports.index.post') }}" method="post">
                <div class="card mb-2">
                    <x-elements.card-header-with-menu :cardTitle="__('firefly.reports')" :route="''" :linkTitle="''"/>
                    <div class="card-body">
                        <p class="text-info">
                            {{ __('firefly.more_info_help') }}
                        </p>
                        <input type="hidden" name="_token" value="{{ csrf_token() }}"/>
                        <div class="row mb-3">
                            <label for="inputReportType" class="col-sm-3 col-form-label">{{ __('firefly.report_type') }}</label>
                            <div class="col-sm-9">
                                <select name="report_type" class="form-control" id="inputReportType">
                                    <option selected label="{{ __('firefly.report_type_default') }}" value="default">{{ __('firefly.report_type_default') }}</option>
                                    <option label="{{ __('firefly.report_type_audit') }}" value="audit">{{ __('firefly.report_type_audit') }}</option>
                                    <option label="{{ __('firefly.report_type_budget') }}" value="budget">{{ __('firefly.report_type_budget') }}</option>
                                    <option label="{{ __('firefly.report_type_category') }}" value="category">{{ __('firefly.report_type_category') }}</option>
                                    <option label="{{ __('firefly.report_type_tag') }}" value="tag">{{ __('firefly.report_type_tag') }}</option>
                                    <option label="{{ __('firefly.report_type_double') }}" value="double">{{ __('firefly.report_type_double') }}</option>
                                </select>
                            </div>
                        </div>
                        <div class="row mb-3">
                            <label for="inputAccounts" class="col-sm-3 col-form-label">{{ __('firefly.report_included_accounts') }}</label>
                            <div class="col-sm-9" id="inputAccountsSelect">
                                <select id="inputAccounts" name="accounts[]" multiple class="form-control">
                                    @foreach($groupedAccounts as $role => $$accountList)
                                        <optgroup label="{{ $role }}">
                                            @foreach($$accountList as $account)
                                                <option
                                                    value="{{ $account->id }}"
                                                    label="{{ $account->name }}@if('sharedAsset' === account_get_meta_field($account, 'accountRole')) ({{ strtolower(__('firefly.shared')) }})@endif">
                                                    {{ $account->name }}@if('sharedAsset' === account_get_meta_field($account, 'accountRole'))({{ strtolower(__('firefly.shared')) }})@endif
                                                </option>
                                            @endforeach
                                        </optgroup>
                                    @endforeach
                                </select>
                            </div>
                        </div>

                        <div class="row mb-3">
                            <label for="inputDateRange" class="col-sm-3 col-form-label">{{ __('firefly.report_date_range') }}</label>
                            <div class="col-sm-9">
                                <input autocomplete="off" type="text" class="form-control" id="inputDateRange" name="daterange"
                                       value="{{ session('start')->format('Y-m-d') }} - {{ session('end')->format('Y-m-d') }}">
                            </div>
                        </div>
                        <div class="row mb-3">
                            <label for="__none__" class="col-sm-3 col-form-label">{{ __('firefly.report_preset_ranges') }}</label>

                            <div class="col-sm-9">
                                @foreach($months as $year => $data)
                                    <a href="#" class="date-select" data-start="{{ $data['start'] }}" data-end="{{ $data['end'] }}">{{ $year }}</a>
                                @if(1 === $customFiscalYear)
                                        <br/>
                                        <a href="#" class="date-select" data-start="{{ $data['fiscal_start'] }}" data-end="{{ $data['fiscal_end'] }}">{{ $year }}
                                            ({{ __('firefly.fiscal_year') }})</a>
                                    @endif
                                    @if(0 === $customFiscalYear)
                                (<a href="#" class="date-select" data-start="{{ $year }}-01-01" data-end="{{ $year }}-03-31">Q1</a>,
                                <a href="#" class="date-select" data-start="{{ $year }}-04-01" data-end="{{ $year }}-06-30">Q2</a>,
                                    <a href="#" class="date-select" data-start="{{ $year }}-07-01" data-end="{{ $year }}-09-30">Q3</a>,
                                        <a href="#" class="date-select" data-start="{{ $year }}-10-01" data-end="{{ $year }}-12-31">Q4</a>)
                                    @endif
                                    <ul class="list-inline">
                                        @foreach($data['months'] as $month)
                                            <li>
                                                <a data-start="{{ $month['start'] }}" data-end="{{ $month['end'] }}" class="date-select"
                                                   href="#">{{ $month['formatted'] }}</a>
                                            </li>
                                        @endforeach
                                    </ul>
                                @endforeach
                            </div>
                        </div>

                    </div>
                </div>
                <div class="card mb-2" id="extra-options-box">
                    <div class="card-header">
                        <h3 class="card-title">{{ __('firefly.reports_extra_options') }}</h3>
                    </div>
                    <div class="card-body" id="extra-options">
                    </div>
                    {{-- extra options loading indicator. --}}
                    <div class="overlay">
                        <div class="spinner-border spinner-border-sm" role="status">
                            <span class="visually-hidden">Loading...</span>
                        </div>
                    </div>
                </div>
                <div class="card mb-2">
                    <div class="card-header">
                        <h3 class="card-title">{{ __('firefly.reports_submit') }}</h3>
                    </div>
                    <div class="card-body">
                        <div class="form-group">
                            <div class="col-sm-offset-3 col-sm-9">
                                <button type="submit" class="btn btn-outline-secondary">{{ __('firefly.submit') }}</button>
                            </div>
                        </div>
                    </div>
                </div>
                </form>
            </div>

        <div class="col-lg-6 col-md-6 col-sm-12 col-xs-12">
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">{{ __('firefly.quick_link_reports') }}</h3>
                </div>
                <div class="card-body">
                    @if('' === $accountList)
                    <p class="text-danger">
                        {{ __('firefly.quick_link_needs_accounts') }}
                    </p>
                    @endif
                    @if('' !== $accountList)
                    <p>
                        {{ __('firefly.quick_link_examples') }}
                    </p>
                    <h4>{{ __('firefly.quick_link_default_report') }}</h4>
                    <ul>
                        <li>
                            <a href="{{ route('reports.report.default',[$accountList, 'currentMonthStart','currentMonthEnd']) }}">{{ __('firefly.report_this_month_quick') }}</a>
                        </li>
                        <li>
                            <a href="{{ route('reports.report.default',[$accountList, 'currentYearStart','currentYearEnd']) }}">{{ __('firefly.report_this_year_quick') }}</a>
                        </li>
                        @if(1 === $customFiscalYear)
                            <li>
                                <a href="{{ route('reports.report.default',[$accountList, 'currentFiscalYearStart','currentFiscalYearEnd']) }}">{{ __('firefly.report_this_fiscal_year_quick') }}</a>
                            </li>
                        @endif
                        <li>
                            <a href="{{ route('reports.report.default',[$accountList, 'previousMonthStart','previousMonthEnd']) }}">{{ __('firefly.report_last_month_quick') }}</a>
                        </li>
                        <li>
                            <a href="{{ route('reports.report.default',[$accountList, $start->format('Ymd'),'currentMonthEnd']) }}">{{ __('firefly.report_all_time_quick') }}</a>
                        </li>
                    </ul>

                    <h4>{{ __('firefly.quick_link_audit_report') }}</h4>
                    <ul>
                        <li>
                            <a href="{{ route('reports.report.audit',[$accountList, 'currentMonthStart','currentMonthEnd']) }}">{{ __('firefly.report_this_month_quick') }}</a>
                        </li>
                        <li>
                            <a href="{{ route('reports.report.audit',[$accountList, 'currentYearStart','currentYearEnd']) }}">{{ __('firefly.report_this_year_quick') }}</a>
                        </li>
                        @if(1 === $customFiscalYear)
                            <li>
                                <a href="{{ route('reports.report.audit',[$accountList, 'currentFiscalYearStart','currentFiscalYearEnd']) }}">{{ __('firefly.report_this_fiscal_year_quick') }}</a>
                            </li>
                        @endif
                        <li>
                            <a href="{{ route('reports.report.audit',[$accountList, $start->format('Ymd'),'currentMonthEnd']) }}">{{ __('firefly.report_all_time_quick') }}</a>
                        </li>
                    </ul>
                    <p>
                        <em>{{ __('firefly.reports_can_bookmark') }}</em>
                    </p>
                    @endif
                </div>
            </div>

        </div>
    </div>
    <div class="empty-high-block">&nbsp;</div>
@endsection

@section('styles')
    <link href="v1/css/bootstrap-multiselect.css?v={{ $FF_BUILD_TIME }}" rel="stylesheet" type="text/css" nonce="{{ $JS_NONCE }}">
@endsection

@section('scripts')
    @vite(['js/pages/generic.js'])
    <script type="text/javascript" nonce="{{ $JS_NONCE }}">
        var minDate = "{{ $start->format('Y-m-d') }}";
        var picker;
        var selectAllText = "{{ trans('firefly.multi_select_select_all') }}";
        var nonSelectedText = "{{ trans('firefly.multi_select_no_selection') }}";
        var nSelectedText = "{{ trans('firefly.multi_select_n_selected') }}";
        var allSelectedText = "{{ trans('firefly.multi_select_all_selected') }}";
        var filterPlaceholder = "{{ trans('firefly.multi_select_filter_placeholder') }}";
    </script>
    <script type="text/javascript" src="v1/js/lib/jquery-4.0.0.min.js?v={{ $FF_BUILD_TIME }}" nonce="{{ $JS_NONCE }}"></script>
    <script type="text/javascript" src="v1/js/lib/daterangepicker.js?v={{ $FF_BUILD_TIME }}" nonce="{{ $JS_NONCE }}"></script>
    <script type="text/javascript" src="v1/js/lib/bootstrap-multiselect.js?v={{ $FF_BUILD_TIME }}" nonce="{{ $JS_NONCE }}"></script>
    <script type="text/javascript" src="v1/js/ff/reports/all.js?v={{ $FF_BUILD_TIME }}" nonce="{{ $JS_NONCE }}"></script>
    <script type="text/javascript" src="v1/js/ff/reports/index.js?v={{ $FF_BUILD_TIME }}" nonce="{{ $JS_NONCE }}"></script>
@endsection
