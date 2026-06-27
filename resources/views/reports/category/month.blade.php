@extends('layout.v3.session')
@section('breadcrumbs')
    {{ Breadcrumbs::render(Route::getCurrentRoute()->getName(), $accountIds, $categoryIds, $start, $end) }}
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
                    <h3 class="card-title">{{ __('firefly.categories') }}</h3>
                </div>
                <div class="card-body p-0" id="categoriesHolder">
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
                    <h3 class="card-title">{{ __('firefly.account_per_category') }}</h3>
                </div>
                <div class="card-body p-0" id="accountPerCategoryHolder">
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
                    <h3 class="card-title">{{ __('firefly.expense_per_source_account') }}</h3>
                </div>
                <div class="card-body">
                    <div class="center-chart">
                        <canvas id="source-out-pie-chart" class="medium-chart" height="250"></canvas>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-lg-6 col-md-6">
            <div class="card mb-2">
                <div class="card-header">
                    <h3 class="card-title">{{ __('firefly.income_per_source_account') }}</h3>
                </div>
                <div class="card-body">
                    <div class="center-chart">
                        <canvas id="source-in-pie-chart" class="medium-chart" height="250"></canvas>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="row">
        <div class="col-lg-6 col-md-6">
            <div class="card mb-2">
                <div class="card-header">
                    <h3 class="card-title">{{ __('firefly.expense_per_destination_account') }}</h3>
                </div>
                <div class="card-body">
                    <div class="center-chart">
                        <canvas id="dest-out-pie-chart" class="medium-chart" height="250"></canvas>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-lg-6 col-md-6">
            <div class="card mb-2">
                <div class="card-header">
                    <h3 class="card-title">{{ __('firefly.income_per_destination_account') }}</h3>
                </div>
                <div class="card-body">
                    <div class="center-chart">
                        <canvas id="dest-in-pie-chart" class="medium-chart" height="250"></canvas>
                    </div>
                </div>
            </div>
        </div>
    </div>

    @foreach($categories as $category)
        <div class="row">
            <div class="col-lg-12">
                <div class="box main_budget_chart">
                    <div class="card-header">
                        <h3 class="card-title">{{ __('firefly.income_and_expenses') }} ({{ $category['name'] }})</h3>
                    </div>
                    <div class="card-body">
                        <canvas class="main_category_canvas wide-chart" data-url="{{ route('chart.category.main', [$accountIds, $category['id'], $start->format('Ymd'), $end->format('Ymd')]) }}" id="in-out-chart-{{ $category['id'] }}" height="400" width="100%"></canvas>
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
                    <h3 class="card-title">{{ __('firefly.average_earning_per_source') }}</h3>
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
    <div class="row">
        <div class="col-lg-6">
            <div class="card mb-2">
                <div class="card-header">
                    <h3 class="card-title">{{ __('firefly.expenses') }} ({{ trans('firefly.topX',['number' => $listLength]) }})</h3>
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
        <div class="col-lg-6">
            <div class="card mb-2">
                <div class="card-header">
                    <h3 class="card-title">{{ __('firefly.income') }} ({{ trans('firefly.topX', ['number' => $listLength]) }})</h3>
                </div>
                <div class="card-body p-0" id="topIncomeHolder">
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

    <script type="text/javascript" src="v1/js/ff/reports/all.js?v={{ $FF_BUILD_TIME }}" nonce="{{ $JS_NONCE }}"></script>
    <script type="text/javascript" nonce="{{ $JS_NONCE }}">
        // to report another URL:
        var startDate = '{{ $start->format('Ymd') }}';
        var endDate = '{{ $end->format('Ymd') }}';
        var $accountIds = '{{ $accountIds }}';
        var categoryIds = '{{ $categoryIds }}';


        var accountsUrl = '{{ route('report-data.category.accounts', [$accountIds, $categoryIds, $start->format('Ymd'), $end->format('Ymd')]) }}';
        var categoriesUrl = '{{ route('report-data.category.categories', [$accountIds, $categoryIds, $start->format('Ymd'), $end->format('Ymd')]) }}';
        var accountPerCategoryUrl = '{{ route('report-data.category.account-per-category', [$accountIds, $categoryIds, $start->format('Ymd'), $end->format('Ymd')]) }}';

        // pie charts:
        var categoryOutUrl = '{{ route('chart.category.category-expense', [$accountIds, $categoryIds, $start->format('Ymd'), $end->format('Ymd')]) }}';
        var categoryInUrl = '{{ route('chart.category.category-income', [$accountIds, $categoryIds, $start->format('Ymd'), $end->format('Ymd')]) }}';
        var budgetsOutUrl = '{{ route('chart.category.budget-expense', [$accountIds, $categoryIds, $start->format('Ymd'), $end->format('Ymd')]) }}';
        var sourceOutUrl = '{{ route('chart.category.source-expense', [$accountIds, $categoryIds, $start->format('Ymd'), $end->format('Ymd')]) }}';
        var sourceInUrl = '{{ route('chart.category.source-income', [$accountIds, $categoryIds, $start->format('Ymd'), $end->format('Ymd')]) }}';
        var destOutUrl = '{{ route('chart.category.dest-expense', [$accountIds, $categoryIds, $start->format('Ymd'), $end->format('Ymd')]) }}';
        var destInUrl = '{{ route('chart.category.dest-income', [$accountIds, $categoryIds, $start->format('Ymd'), $end->format('Ymd')]) }}';

        var avgExpensesUrl = '{{ route('report-data.category.avg-expenses', [$accountIds, $categoryIds, $start->format('Ymd'), $end->format('Ymd')]) }}';
        var topExpensesUrl = '{{ route('report-data.category.top-expenses', [$accountIds, $categoryIds, $start->format('Ymd'), $end->format('Ymd')]) }}';
        var avgIncomeUrl = '{{ route('report-data.category.avg-income', [$accountIds, $categoryIds, $start->format('Ymd'), $end->format('Ymd')]) }}';
        var topIncomeUrl = '{{ route('report-data.category.top-income', [$accountIds, $categoryIds, $start->format('Ymd'), $end->format('Ymd')]) }}';

    </script>
    <script type="text/javascript" src="v1/js/ff/reports/category/month.js?v={{ $FF_BUILD_TIME }}" nonce="{{ $JS_NONCE }}"></script>

@endsection

@section('styles')
    <link rel="stylesheet" href="v1/css/bootstrap-sortable.css?v={{ $FF_BUILD_TIME }}" type="text/css" media="all" nonce="{{ $JS_NONCE }}">
@endsection
