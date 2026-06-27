@extends('layout.v3.session')
@section('breadcrumbs')
    {{ Breadcrumbs::render(Route::getCurrentRoute()->getName(), $accountIds, $budgetIds, $start, $end) }}
@endsection
@section('content')
    <div class="row">
        <div class="col-lg-6 col-md-6 col-sm-12 col-xs-12">
            <div class="card mb-2">
                <div class="card-header">
                    <h3 class="card-title">{{ __('firefly.accounts') }}</h3>
                </div>
                <div class="card-body p-0" id="accountsHolder">
                </div>
                {{-- loading indicator --}}
                <div class="overlay text-center m-2">
                    <div class="spinner-border spinner-border-sm" role="status">
                        <span class="visually-hidden">Loading...</span>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-lg-6 col-md-6 col-sm-12 col-xs-12">
            <div class="card mb-2">
                <div class="card-header">
                    <h3 class="card-title">{{ __('firefly.budgets') }}</h3>
                </div>
                <div class="card-body p-0" id="budgetsHolder">
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
    <div class="row">
        <div class="col-lg-12 col-md-12 col-sm-12 col-xs-12">
            <div class="card mb-2">
                <div class="card-header">
                    <h3 class="card-title">{{ __('firefly.account_per_budget') }}</h3>
                </div>
                <div class="card-body p-0" id="accountPerbudgetHolder">
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
    <div class="row">
        <div class="col-lg-6 col-md-6">
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
        <div class="col-lg-6 col-md-6">
            <div class="card mb-2">
                <div class="card-header">
                    <h3 class="card-title">{{ __('firefly.expense_per_category') }}</h3>
                </div>
                <div class="card-body">
                    <div class="center-chart">
                        <canvas id="categories-out-pie-chart" class="medium-chart" height="250"></canvas>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="row">
        <div class="col-lg-6 col-md-6">
            <div class="card mb-2">
                <div class="card-header">
                    <h3 class="card-title">{{ __('firefly.expense_per_source_account') }}</h3>
                </div>
                <div class="card-body">
                    <div class="center-chart">
                        <canvas id="source-accounts-pie-chart" class="medium-chart" height="250"></canvas>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-lg-6 col-md-6">
            <div class="card mb-2">
                <div class="card-header">
                    <h3 class="card-title">{{ __('firefly.expense_per_destination_account') }}</h3>
                </div>
                <div class="card-body">
                    <div class="center-chart">
                        <canvas id="dest-accounts-pie-chart" class="medium-chart" height="250"></canvas>
                    </div>
                </div>
            </div>
        </div>
    </div>

    @foreach($budgets as $budget)
        <div class="row">
            <div class="col-lg-12">
                <div class="card mb-2 main_budget_chart">
                    <div class="card-header">
                        <h3 class="card-title">{{ __('firefly.expenses') }} ({{ $budget->name }})</h3>
                    </div>
                    <div class="card-body">
                        <canvas class="main_budget_canvas wide-chart" data-url="{{ route('chart.budget.main', [$accountIds, $budget->id, $start->format('Ymd'), $end->format('Ymd')]) }}" id="in-out-chart-{{ $budget->id }}" height="400" width="100%"></canvas>
                    </div>
                </div>
            </div>
        </div>
    @endforeach
    <div class="row">
        <div class="col-lg-6">
            <div class="card mb-2">
                <div class="card-header">
                    <h3 class="card-title">{{ __('firefly.average_spending_per_destination') }}</h3>
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
                    <h3 class="card-title">{{ __('firefly.expenses') }} ({{ trans('firefly.topX', ['number' => $listLength]) }})</h3>
                </div>
                <div class="card-body p-0" id="topExpensesHolder">
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
        var $accountIds = '{{ $accountIds }}';
        var $budgetIds = '{{ $budgetIds }}';

        // html block URL's:
        var accountsUrl = '{{ route('report-data.budget.accounts', [$accountIds, $budgetIds, $start->format('Ymd'), $end->format('Ymd')]) }}';
        var budgetsUrl = '{{ route('report-data.budget.budgets', [$accountIds, $budgetIds, $start->format('Ymd'), $end->format('Ymd')]) }}';
        var accountPerBudgetUrl = '{{ route('report-data.budget.account-per-budget', [$accountIds, $budgetIds, $start->format('Ymd'), $end->format('Ymd')]) }}';
        var avgExpensesUrl = '{{ route('report-data.budget.avg-expenses', [$accountIds, $budgetIds, $start->format('Ymd'), $end->format('Ymd')]) }}';
        var topExpensesUrl = '{{ route('report-data.budget.top-expenses', [$accountIds, $budgetIds, $start->format('Ymd'), $end->format('Ymd')]) }}';

        var budgetExpenseUrl = '{{ route('chart.budget.budget-expense', [$accountIds, $budgetIds, $start->format('Ymd'), $end->format('Ymd')]) }}';
        var categoryExpenseUrl = '{{ route('chart.budget.category-expense', [$accountIds, $budgetIds, $start->format('Ymd'), $end->format('Ymd')]) }}';
        var sourceExpenseUrl = '{{ route('chart.budget.source-account-expense', [$accountIds, $budgetIds, $start->format('Ymd'), $end->format('Ymd')]) }}';
        var destinationExpenseUrl = '{{ route('chart.budget.destination-account-expense', [$accountIds, $budgetIds, $start->format('Ymd'), $end->format('Ymd')]) }}';
    </script>


    <script type="text/javascript" src="v1/js/ff/reports/all.js?v={{ $FF_BUILD_TIME }}" nonce="{{ $JS_NONCE }}"></script>
    <script type="text/javascript" src="v1/js/ff/reports/budget/month.js?v={{ $FF_BUILD_TIME }}" nonce="{{ $JS_NONCE }}"></script>

@endsection

@section('styles')
    <link rel="stylesheet" href="v1/css/bootstrap-sortable.css?v={{ $FF_BUILD_TIME }}" type="text/css" media="all" nonce="{{ $JS_NONCE }}">
@endsection
