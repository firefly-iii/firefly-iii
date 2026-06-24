// date ranges
var ranges = {};
@foreach($dateRangeConfig['ranges'] as $title => $range)
    ranges["{{ $title }}"] = [moment("{{ $range[0]->format('Y-m-d') }}"), moment("{{ $range[1]->format('Y-m-d') }}")];
@endforeach

// date range meta configuration
var dateRangeMeta = {
title: "{{ $dateRangeTitle }}",
url: "{{ route('daterange') }}",
labels: {
apply: "{{ __('firefly.apply') }}",
cancel: "{{ __('firefly.cancel') }}",
from: "{{ __('firefly.from') }}",
to: "{{ __('firefly.to') }}",
customRange: "{{ __('firefly.customRange') }}"
}
};

// date range actual configuration:
var dateRangeConfig = {
startDate: moment("{{ $dateRangeConfig['start'] }}"),
endDate: moment("{{ $dateRangeConfig['end'] }}"),
ranges: ranges

};

var uid = "{{ $uid }}";
var anonymous = {{ $anonymous }};
var language = "{{ $language }}";
var locale = "{{ $locale }}";
var currencyCode = '{{ $currencyCode }}';
var currencySymbol = '{{ $currencySymbol }}';
var mon_decimal_point = "{{ $accountingLocaleInfo['mon_decimal_point'] }}";
var mon_thousands_sep = "{{ $accountingLocaleInfo['mon_thousands_sep'] }}";
var frac_digits = {{ $accountingLocaleInfo['frac_digits'] ?? '0' }};
var noDataForChart = '{{ trans('firefly.no_data_for_chart') }}';
var showFullList = '{{ trans('firefly.show_full_list') }}';
var showOnlyTop = '{{ trans('firefly.show_only_top',['number' => $listLength]) }}';
var accountingConfig = {!! json_encode($accountingLocaleInfo['format']) !!};
var token = '{{ csrf_token() }}';
var sessionStart = '{{ session('start')->format('Y-m-d') }}';
var sessionEnd = '{{ session('end')->format('Y-m-d') }}';
var todayText = ' {{ trans('firefly.today') }}';

// some formatting stuff:
var month_and_day_js = "{{ trans('config.month_and_day_js') }}";
var date_time_js = "{{ trans('config.date_time_js') }}";
var acc_config_new = {format: accountingConfig};

// strings and translations used often:
var helpPageTitle = "{{ trans('firefly.help_for_this_page') }}";
var helpPageBody = "{{ trans('firefly.help_for_this_page_body') }}";

var anonymous_warning_on_txt = "{{ trans('firefly.anonymous_warning_on') }}";
var anonymous_warning_off_txt= "{{ trans('firefly.anonymous_warning_off') }}";

var edit_selected_txt = "{{ trans('firefly.mass_edit') }}";
var edit_bulk_selected_txt = "{{ trans('firefly.bulk_edit') }}";
var delete_selected_txt = "{{ trans('firefly.mass_delete') }}";

var mass_edit_url   = '{{ route('transactions.mass.edit', ['']) }}';
var bulk_edit_url   = '{{ route('transactions.bulk.edit', ['']) }}';
var mass_delete_url = '{{ route('transactions.mass.delete', ['']) }}';

// for demo:
var nextLabel = "{{ trans('firefly.intro_next_label') }}";
var prevLabel = "{{ trans('firefly.intro_prev_label') }}";
var skipLabel = "{{ trans('firefly.intro_skip_label') }}";
var doneLabel = "{{ trans('firefly.intro_done_label') }}";
