@extends('layout.v3.session')


    {{ Breadcrumbs.render(Route.getCurrentRoute.getName, accountIds, start, end) }}
@endsection

@section('content')

    {{-- chart --}}
    <div class="row">
        <div class="col-lg-12 col-md-12 col-sm-12">
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">{{ 'accountBalances'|_ }}</h3>
                </div>
                <div class="card-body">
                    <canvas id="account-balances-chart" class="wide-chart" height="400" width="100%"></canvas>
                </div>
            </div>
        </div>
    </div>

    {{-- account balances and income vs. expense --}}
    <div class="row">
        <div class="col-lg-6 col-md-6 col-sm-6">
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">{{ 'accountBalances'|_ }}</h3>
                </div>
                <div class="card-body p-0" id="accountReport">
                </div>
                {{-- loading indicator --}}
                <div class="overlay">
                    <div class="spinner-border spinner-border-sm" role="status">
  <span class="visually-hidden">Loading...</span>
</div>
                </div>
            </div>
        </div>
        <div class="col-lg-6 col-md-6 col-sm-6">
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">{{ 'incomeVsExpenses'|_ }}</h3>
                </div>
                <div class="card-body p-0" id="incomeVsExpenseReport">
                </div>
                {{-- loading indicator --}}
                <div class="overlay">
                    <div class="spinner-border spinner-border-sm" role="status">
  <span class="visually-hidden">Loading...</span>
</div>
                </div>
            </div>
        </div>
    </div>

    {{-- in and out --}}
    <div class="row">
        <div class="col-lg-6 col-md-6 col-sm-12 col-xs-12">
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">{{ 'income'|_ }}</h3>
                </div>
                <div class="card-body p-0" id="incomeReport">
                </div>
                {{-- loading indicator --}}
                <div class="overlay">
                    <div class="spinner-border spinner-border-sm" role="status">
  <span class="visually-hidden">Loading...</span>
</div>
                </div>
            </div>
        </div>
        <div class="col-lg-6 col-md-6 col-sm-12 col-xs-12">
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">{{ __('firefly.expenses') }}</h3>
                </div>
                <div class="card-body p-0" id="expenseReport">
                </div>
                {{-- loading indicator --}}
                <div class="overlay">
                    <div class="spinner-border spinner-border-sm" role="status">
  <span class="visually-hidden">Loading...</span>
</div>
                </div>
            </div>
        </div>
    </div>

    <div class="row">
        <div class="col-lg-8 col-md-8 col-sm-12">
            <div class="card">

                <div class="card-header">
                    <h3 class="card-title">{{ __('firefly.budgets') }}</h3>
                </div>
                <div class="card-body p-0" id="budgetReport">
                </div>
                {{-- loading indicator --}}
                <div class="overlay">
                    <div class="spinner-border spinner-border-sm" role="status">
  <span class="visually-hidden">Loading...</span>
</div>
                </div>
            </div>


        </div>
        <div class="col-lg-4 col-md-4 col-sm-12">
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">{{ 'categories'|_ }}</h3>
                </div>
                <div class="card-body p-0" id="categoryReport"></div>
                {{-- loading indicator --}}
                <div class="overlay">
                    <div class="spinner-border spinner-border-sm" role="status">
  <span class="visually-hidden">Loading...</span>
</div>
                </div>
            </div>
        </div>
    </div>
    <div class="row">
        <div class="col-lg-12 col-md-12 col-sm-12">
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">{{ __('firefly.budgets') }} ({{ 'splitByAccount'|_|lower }})</h3>
                </div>
                <div class="card-body p-0" id="balanceReport">
                </div>
                {{-- loading indicator --}}
                <div class="overlay">
                    <div class="spinner-border spinner-border-sm" role="status">
  <span class="visually-hidden">Loading...</span>
</div>
                </div>
            </div>

        </div>
    </div>
    <div class="row">
        <div class="col-lg-12 col-md-12 col-sm-12">
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">{{ 'bills'|_ }}</h3>
                </div>
                <div class="card-body p-0" id="billReport"></div>
                {{-- loading indicator --}}
                <div class="overlay">
                    <div class="spinner-border spinner-border-sm" role="status">
  <span class="visually-hidden">Loading...</span>
</div>
                </div>
            </div>
        </div>
    </div>
@endsection
@section('styles')
    <link rel="stylesheet" href="v1/css/bootstrap-sortable.css?v={{ $FF_BUILD_TIME }}" type="text/css" media="all" nonce="{{ $JS_NONCE }}">
@endsection
@section('scripts')

    <script type="text/javascript" src="v1/js/lib/bootstrap-sortable.js?v={{ $FF_BUILD_TIME }}" nonce="{{ $JS_NONCE }}"></script>
    <script type="text/javascript" src="v1/js/lib/Chart.bundle.min.js?v={{ $FF_BUILD_TIME }}" nonce="{{ $JS_NONCE }}"></script>
    <script type="text/javascript" src="v1/js/ff/charts.defaults.js?v={{ $FF_BUILD_TIME }}" nonce="{{ $JS_NONCE }}"></script>
    <script type="text/javascript" src="v1/js/ff/charts.js?v={{ $FF_BUILD_TIME }}" nonce="{{ $JS_NONCE }}"></script>
    <script type="text/javascript" nonce="{{ $JS_NONCE }}">
        var year = {{ $start->year }};
        var month = {{ $start->month }};

        // to report another URL:
        var startDate = '{{ $start->format('Ymd') }}';
        var endDate = '{{ $end->format('Ymd') }}';
        var reportType = '{{ reportType }}';
        var accountIds = '{{ accountIds }}';

        var accountReportUrl = '{{ route('report-data.account.general', [accountIds, $start->format('Ymd'), $end->format('Ymd')]) }}';
        var categoryReportUrl = '{{ route('report-data.category.operations', [accountIds, $start->format('Ymd'), $end->format('Ymd')]) }}';
        var budgetReportUrl = '{{ route('report-data.budget.general', [accountIds, $start->format('Ymd'), $end->format('Ymd')]) }}';
        var balanceReportUrl = '{{ route('report-data.balance.general', [accountIds, $start->format('Ymd'), $end->format('Ymd')]) }}';
        var incomeReportUrl = '{{ route('report-data.operations.income', [accountIds, $start->format('Ymd'), $end->format('Ymd')]) }}';
        var expenseReportUrl = '{{ route('report-data.operations.expenses', [accountIds, $start->format('Ymd'), $end->format('Ymd')]) }}';
        var incExpReportUrl = '{{ route('report-data.operations.operations', [accountIds, $start->format('Ymd'), $end->format('Ymd')]) }}';
        var billReportUrl = '{{ route('report-data.bills.overview', [accountIds, $start->format('Ymd'), $end->format('Ymd')]) }}';

        var accountChartUrl = '{{ route('chart.account.report', [accountIds, $start->format('Ymd'), $end->format('Ymd')]) }}';

    </script>
    <script type="text/javascript" src="v1/js/ff/reports/all.js?v={{ $FF_BUILD_TIME }}" nonce="{{ $JS_NONCE }}"></script>
    <script type="text/javascript" src="v1/js/ff/reports/default/all.js?v={{ $FF_BUILD_TIME }}" nonce="{{ $JS_NONCE }}"></script>
    <script type="text/javascript" src="v1/js/ff/reports/default/month.js?v={{ $FF_BUILD_TIME }}" nonce="{{ $JS_NONCE }}"></script>
@endsection
