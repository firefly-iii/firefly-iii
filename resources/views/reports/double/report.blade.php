@extends('layout.v3.session')
@section('breadcrumbs')
    {{ Breadcrumbs::render(Route::getCurrentRoute()->getName(), $accountIds, $doubleIds, $start, $end) }}
@endsection

@section('content')

    <div class="row">
        <div class="col-lg-6 col-md-12 col-sm-12 col-xs-12">
            <div class="card mb-2">
                <div class="card-header">
                    <h3 class="card-title">{{ __('firefly.in_out_accounts') }}</h3>
                </div>
                <div class="card-body p-0" id="opsAccounts">
                    {{-- loading indicator --}}
                    <div class="overlay text-center m-2">
                        <div class="spinner-border spinner-border-sm" role="status">
  <span class="visually-hidden">Loading...</span>
</div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-lg-6 col-md-12 col-sm-12 col-xs-12">
            <div class="card mb-2">
                <div class="card-header">
                    <h3 class="card-title">{{ __('firefly.in_out_accounts_per_asset') }}</h3>
                </div>
                <div class="card-body p-0" id="opsAccountsAsset">
                    {{-- loading indicator --}}
                    <div class="overlay text-center m-2">
                        <div class="spinner-border spinner-border-sm" role="status">
  <span class="visually-hidden">Loading...</span>
</div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="row">
        <div class="col-lg-4 col-md-6">
            <div class="card mb-2">
                <div class="card-header">
                    <h3 class="card-title">{{ __('firefly.expense_per_category') }}</h3>
                </div>
                <div class="card-body">
                    <div class="center-chart">
                        <canvas id="category-out-pie-chart" class="medium-chart" height="250"></canvas>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-lg-4 col-md-6">
            <div class="card mb-2">
                <div class="card-header">
                    <h3 class="card-title">{{ __('firefly.income_per_category') }}</h3>
                </div>
                <div class="card-body">
                    <div class="center-chart">
                        <canvas id="category-in-pie-chart" class="medium-chart" height="250"></canvas>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-lg-4 col-md-6">
            <div class="card mb-2">
                <div class="card-header">
                    <h3 class="card-title">{{ __('firefly.expense_per_budget') }}</h3>
                </div>
                <div class="card-body">
                    <div class="center-chart">
                        <canvas id="budgets-out-pie-chart" class="medium-chart" height="250"></canvas>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="row">
        <div class="col-lg-6 col-md-6">
            <div class="card mb-2">
                <div class="card-header">
                    <h3 class="card-title">{{ __('firefly.expense_per_tag') }}</h3>
                </div>
                <div class="card-body">
                    <div class="center-chart">
                        <canvas id="tag-out-pie-chart" class="medium-chart" height="250"></canvas>
                    </div>
                </div>
                <div class="card-footer">
                    <p class="text-info">
                        <em>{{ __('firefly.double_report_expenses_charted_once') }}</em>
                    </p>
                </div>
            </div>
        </div>
        <div class="col-lg-6 col-md-6">
            <div class="card mb-2">
                <div class="card-header">
                    <h3 class="card-title">{{ __('firefly.income_per_tag') }}</h3>
                </div>
                <div class="card-body">
                    <div class="center-chart">
                        <canvas id="tag-in-pie-chart" class="medium-chart" height="250"></canvas>
                    </div>
                </div>
                <div class="card-footer">
                    <p class="text-info">
                        <em>{{ __('firefly.double_report_expenses_charted_once') }}</em>
                    </p>
                </div>
            </div>
        </div>
    </div>

    @foreach($doubles as $account)
        <div class="row">
            <div class="col-lg-12">
                <div class="card mb-2" id="incomeAndExpensesChart">
                    <div class="card-header">
                        <h3 class="card-title">{{ __('firefly.income_and_expenses') }} ({{ $account['name'] }}) @if(null !== $account['iban'])({{ $account['iban'] }})@endif</h3>
                    </div>
                    <div class="card-body">
                        <canvas class="main_double_canvas wide-chart"
                                data-url="{{ route('chart.double.main', [$accountIds, $account['id'], $start->format('Ymd'), $end->format('Ymd')]) }}"
                                id="in-out-chart-{{ $account['id'] }}" height="400" width="100%"></canvas>
                    </div>
                </div>
            </div>
        </div>
    @endforeach

    <div class="row">
        <div class="col-lg-6 col-md-6 col-sm-12 col-xs-12">
            <div class="card mb-2">
                <div class="card-header">
                    <h3 class="card-title">{{ __('firefly.expenses') }} ({{ trans('firefly.topX', ['number' => $listLength]) }})</h3>
                </div>
                <div class="card-body p-0" id="topExpensesHolder">
                    {{-- loading indicator --}}
                    <div class="overlay text-center m-2">
                        <div class="spinner-border spinner-border-sm" role="status">
  <span class="visually-hidden">Loading...</span>
</div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-lg-6 col-md-6 col-sm-12 col-xs-12">
            <div class="card mb-2">
                <div class="card-header">
                    <h3 class="card-title">{{ __('firefly.income') }} ({{ trans('firefly.topX', ['number' => $listLength]) }})</h3>
                </div>
                <div class="card-body p-0" id="topIncomeHolder">
                    {{-- loading indicator --}}
                    <div class="overlay text-center m-2">
                        <div class="spinner-border spinner-border-sm" role="status">
  <span class="visually-hidden">Loading...</span>
</div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="row">
        <div class="col-lg-6">
            <div class="card mb-2">
                <div class="card-header">
                    <h3 class="card-title">{{ __('firefly.average_spending_per_source') }}</h3>
                </div>
                <div class="card-body p-0" id="avgExpensesHolder">
                </div>
                {{-- loading indicator --}}
                <div class="overlay text-center m-2">
                    <div class="spinner-border spinner-border-sm" role="status">
  <span class="visually-hidden">Loading...</span>
</div>
                </div>
            </div>
        </div>
        <div class="col-lg-6">
            <div class="card mb-2">
                <div class="card-header">
                    <h3 class="card-title">{{ __('firefly.average_earning_per_destination') }}</h3>
                </div>
                <div class="card-body p-0" id="avgIncomeHolder">
                </div>
                {{-- loading indicator --}}
                <div class="overlay text-center m-2">
                    <div class="spinner-border spinner-border-sm" role="status">
  <span class="visually-hidden">Loading...</span>
</div>
                </div>
            </div>
        </div>
    </div>

@endsection

@section('scripts')
    @vite(['js/pages/generic.js'])
    <script type="text/javascript" src="v1/js/lib/Chart.bundle.min.js?v={{ $FF_BUILD_TIME }}" nonce="{{ $JS_NONCE }}"></script>
    <script type="text/javascript" src="v1/js/ff/charts.defaults.js?v={{ $FF_BUILD_TIME }}" nonce="{{ $JS_NONCE }}"></script>
    <script type="text/javascript" src="v1/js/ff/charts.js?v={{ $FF_BUILD_TIME }}" nonce="{{ $JS_NONCE }}"></script>
    <script type="text/javascript" src="v1/js/lib/bootstrap-sortable.js?v={{ $FF_BUILD_TIME }}" nonce="{{ $JS_NONCE }}"></script>

    <script type="text/javascript" nonce="{{ $JS_NONCE }}">
        // to report another URL:
        var startDate = '{{ $start->format('Ymd') }}';
        var endDate = '{{ $end->format('Ymd') }}';
        var accountIds = '{{ $accountIds }}';
        var doubleIds = '{{ $doubleIds }}';


        // html blocks.
        var opsAccountsUrl = '{{ route('report-data.double.operations', [$accountIds, $doubleIds, $start->format('Ymd'), $end->format('Ymd')]) }}';
        var opsAccountsAssetUrl = '{{ route('report-data.double.ops-asset', [$accountIds, $doubleIds, $start->format('Ymd'), $end->format('Ymd')]) }}';

        // pie charts:
        var categoryOutUrl = '{{ route('chart.double.category-expense', [$accountIds, $doubleIds, $start->format('Ymd'), $end->format('Ymd')]) }}';
        var categoryInUrl = '{{ route('chart.double.category-income', [$accountIds, $doubleIds, $start->format('Ymd'), $end->format('Ymd')]) }}';
        var budgetsOutUrl = '{{ route('chart.double.budget-expense', [$accountIds, $doubleIds, $start->format('Ymd'), $end->format('Ymd')]) }}';
        var tagOutUrl = '{{ route('chart.double.tag-expense', [$accountIds, $doubleIds, $start->format('Ymd'), $end->format('Ymd')]) }}';
        var tagInUrl = '{{ route('chart.double.tag-income', [$accountIds, $doubleIds, $start->format('Ymd'), $end->format('Ymd')]) }}';

        var avgExpensesUrl = '{{ route('report-data.double.avg-expenses', [$accountIds, $doubleIds, $start->format('Ymd'), $end->format('Ymd')]) }}';
        var topExpensesUrl = '{{ route('report-data.double.top-expenses', [$accountIds, $doubleIds, $start->format('Ymd'), $end->format('Ymd')]) }}';
        var avgIncomeUrl = '{{ route('report-data.double.avg-income', [$accountIds, $doubleIds, $start->format('Ymd'), $end->format('Ymd')]) }}';
        var topIncomeUrl = '{{ route('report-data.double.top-income', [$accountIds, $doubleIds, $start->format('Ymd'), $end->format('Ymd')]) }}';

    </script>
    <script type="text/javascript" src="v1/js/ff/reports/all.js?v={{ $FF_BUILD_TIME }}" nonce="{{ $JS_NONCE }}"></script>
    <script type="text/javascript" src="v1/js/ff/reports/double/month.js?v={{ $FF_BUILD_TIME }}" nonce="{{ $JS_NONCE }}"></script>

@endsection

@section('styles')
    <link rel="stylesheet" href="v1/css/bootstrap-sortable.css?v={{ $FF_BUILD_TIME }}" type="text/css" media="all" nonce="{{ $JS_NONCE }}">
@endsection
