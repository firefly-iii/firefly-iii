@extends('layout.v3.session')


    {{ Breadcrumbs.render(Route.getCurrentRoute.getName, accountIds, doubleIds, start, end) }}
@endsection

@section('content')

    <div class="row">
        <div class="col-lg-6 col-md-12 col-sm-12 col-xs-12">
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">{{ 'in_out_accounts'|_ }}</h3>
                </div>
                <div class="card-body p-0" id="opsAccounts">
                    {{-- loading indicator --}}
                    <div class="overlay">
                        <div class="spinner-border spinner-border-sm" role="status">
  <span class="visually-hidden">Loading...</span>
</div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-lg-6 col-md-12 col-sm-12 col-xs-12">
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">{{ 'in_out_accounts_per_asset'|_ }}</h3>
                </div>
                <div class="card-body p-0" id="opsAccountsAsset">
                    {{-- loading indicator --}}
                    <div class="overlay">
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
            <div class="card">
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
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">{{ 'income_per_category'|_ }}</h3>
                </div>
                <div class="card-body">
                    <div class="center-chart">
                        <canvas id="category-in-pie-chart" class="medium-chart" height="250"></canvas>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-lg-4 col-md-6">
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">{{ 'expense_per_budget'|_ }}</h3>
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
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">{{ 'expense_per_tag'|_ }}</h3>
                </div>
                <div class="card-body">
                    <div class="center-chart">
                        <canvas id="tag-out-pie-chart" class="medium-chart" height="250"></canvas>
                    </div>
                </div>
                <div class="card-footer">
                    <p class="text-info">
                        <em>{{ 'double_report_expenses_charted_once'|_ }}</em>
                    </p>
                </div>
            </div>
        </div>
        <div class="col-lg-6 col-md-6">
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">{{ 'income_per_tag'|_ }}</h3>
                </div>
                <div class="card-body">
                    <div class="center-chart">
                        <canvas id="tag-in-pie-chart" class="medium-chart" height="250"></canvas>
                    </div>
                </div>
                <div class="card-footer">
                    <p class="text-info">
                        <em>{{ 'double_report_expenses_charted_once'|_ }}</em>
                    </p>
                </div>
            </div>
        </div>
    </div>


    {% for account in doubles %}
        <div class="row">
            <div class="col-lg-12">
                <div class="card" id="incomeAndExpensesChart">
                    <div class="card-header">
                        <h3 class="card-title">{{ 'income_and_expenses'|_ }} ({{ account.name }}) {% if account.iban %}({{ account.iban }})@endif</h3>
                    </div>
                    <div class="card-body">
                        <canvas class="main_double_canvas"
                                data-url="{{ route('chart.double.main', [accountIds, account.id, $start->format('Ymd'), $end->format('Ymd')]) }}"
                                id="in-out-chart-{{ account.id }}" class="wide-chart" height="400" width="100%"></canvas>
                    </div>
                </div>
            </div>
        </div>
    @endforeach

    <div class="row">
        <div class="col-lg-6 col-md-6 col-sm-12 col-xs-12">
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">{{ __('firefly.expenses') }} ({{ trans('firefly.topX', {number: listLength}) }})</h3>
                </div>
                <div class="card-body p-0" id="topExpensesHolder">
                    {{-- loading indicator --}}
                    <div class="overlay">
                        <div class="spinner-border spinner-border-sm" role="status">
  <span class="visually-hidden">Loading...</span>
</div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-lg-6 col-md-6 col-sm-12 col-xs-12">
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">{{ 'income'|_ }} ({{ trans('firefly.topX', {number: listLength}) }})</h3>
                </div>
                <div class="card-body p-0" id="topIncomeHolder">
                    {{-- loading indicator --}}
                    <div class="overlay">
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
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">{{ 'average_spending_per_source'|_ }}</h3>
                </div>
                <div class="card-body p-0" id="avgExpensesHolder">
                </div>
                {{-- loading indicator --}}
                <div class="overlay">
                    <div class="spinner-border spinner-border-sm" role="status">
  <span class="visually-hidden">Loading...</span>
</div>
                </div>
            </div>
        </div>
        <div class="col-lg-6">
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">{{ 'average_earning_per_destination'|_ }}</h3>
                </div>
                <div class="card-body p-0" id="avgIncomeHolder">
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

@endsection

@section('scripts')
    <script type="text/javascript" src="v1/js/lib/Chart.bundle.min.js?v={{ $FF_BUILD_TIME }}" nonce="{{ $JS_NONCE }}"></script>
    <script type="text/javascript" src="v1/js/ff/charts.defaults.js?v={{ $FF_BUILD_TIME }}" nonce="{{ $JS_NONCE }}"></script>
    <script type="text/javascript" src="v1/js/ff/charts.js?v={{ $FF_BUILD_TIME }}" nonce="{{ $JS_NONCE }}"></script>
    <script type="text/javascript" src="v1/js/lib/bootstrap-sortable.js?v={{ $FF_BUILD_TIME }}" nonce="{{ $JS_NONCE }}"></script>

    <script type="text/javascript" nonce="{{ $JS_NONCE }}">
        // to report another URL:
        var startDate = '{{ $start->format('Ymd') }}';
        var endDate = '{{ $end->format('Ymd') }}';
        var accountIds = '{{ accountIds }}';
        var doubleIds = '{{ doubleIds }}';


        // html blocks.
        var opsAccountsUrl = '{{ route('report-data.double.operations', [accountIds, doubleIds, $start->format('Ymd'), $end->format('Ymd')]) }}';
        var opsAccountsAssetUrl = '{{ route('report-data.double.ops-asset', [accountIds, doubleIds, $start->format('Ymd'), $end->format('Ymd')]) }}';

        // pie charts:
        var categoryOutUrl = '{{ route('chart.double.category-expense', [accountIds, doubleIds, $start->format('Ymd'), $end->format('Ymd')]) }}';
        var categoryInUrl = '{{ route('chart.double.category-income', [accountIds, doubleIds, $start->format('Ymd'), $end->format('Ymd')]) }}';
        var budgetsOutUrl = '{{ route('chart.double.budget-expense', [accountIds, doubleIds, $start->format('Ymd'), $end->format('Ymd')]) }}';
        var tagOutUrl = '{{ route('chart.double.tag-expense', [accountIds, doubleIds, $start->format('Ymd'), $end->format('Ymd')]) }}';
        var tagInUrl = '{{ route('chart.double.tag-income', [accountIds, doubleIds, $start->format('Ymd'), $end->format('Ymd')]) }}';

        var avgExpensesUrl = '{{ route('report-data.double.avg-expenses', [accountIds, doubleIds, $start->format('Ymd'), $end->format('Ymd')]) }}';
        var topExpensesUrl = '{{ route('report-data.double.top-expenses', [accountIds, doubleIds, $start->format('Ymd'), $end->format('Ymd')]) }}';
        var avgIncomeUrl = '{{ route('report-data.double.avg-income', [accountIds, doubleIds, $start->format('Ymd'), $end->format('Ymd')]) }}';
        var topIncomeUrl = '{{ route('report-data.double.top-income', [accountIds, doubleIds, $start->format('Ymd'), $end->format('Ymd')]) }}';

    </script>
    <script type="text/javascript" src="v1/js/ff/reports/all.js?v={{ $FF_BUILD_TIME }}" nonce="{{ $JS_NONCE }}"></script>
    <script type="text/javascript" src="v1/js/ff/reports/double/month.js?v={{ $FF_BUILD_TIME }}" nonce="{{ $JS_NONCE }}"></script>

@endsection

@section('styles')
    <link rel="stylesheet" href="v1/css/bootstrap-sortable.css?v={{ $FF_BUILD_TIME }}" type="text/css" media="all" nonce="{{ $JS_NONCE }}">
@endsection
