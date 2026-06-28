@extends('layout.v3.session')
@section('breadcrumbs')
    {{ Breadcrumbs::render(Route::getCurrentRoute()->getName(), $accountIds, $tagIds, $start, $end) }}
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
                    <h3 class="card-title">{{ __('firefly.tags') }}</h3>
                </div>
                <div class="card-body p-0" id="tagsHolder">
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
                    <h3 class="card-title">{{ __('firefly.account_per_tag') }}</h3>
                </div>
                <div class="card-body p-0" id="accountPerTagHolder">
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
                    <h3 class="card-title">{{ __('firefly.expense_per_tag') }}</h3>
                </div>
                <div class="card-body">
                    <div class="center-chart">
                        <canvas id="tag-out-pie-chart" class="medium-chart" height="250"></canvas>
                    </div>
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

    @foreach($tags as $tag)
        <div class="row">
            <div class="col-lg-12">
                <div class="box main_budget_chart">
                    <div class="card-header">
                        <h3 class="card-title">{{ __('firefly.income_and_expenses') }} ({{ $tag->tag }})</h3>
                    </div>
                    <div class="card-body">
                        <canvas class="main_tag_canvas wide-chart"
                                data-url="{{ route('chart.tag.main', [$accountIds, $tag->id, $start->format('Ymd'), $end->format('Ymd')]) }}"
                                id="in-out-chart-{{ $tag->id }}"  height="400" width="100%"></canvas>
                    </div>
                    <div class="card-footer">
                        <p class="text-info"><em>{{ __('firefly.tag_report_chart_single_tag') }}</em></p>
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
        var accountIds = '{{ $accountIds }}';
        var tagIds = '{{ $tagIds }}';


        var accountsUrl = '{{ route('report-data.tag.accounts', [$accountIds, $tagIds, $start->format('Ymd'), $end->format('Ymd')]) }}';
        var tagsUrl = '{{ route('report-data.tag.tags', [$accountIds, $tagIds, $start->format('Ymd'), $end->format('Ymd')]) }}';
        var accountPerTagUrl = '{{ route('report-data.tag.account-per-tag', [$accountIds, $tagIds, $start->format('Ymd'), $end->format('Ymd')]) }}';

        // pie charts:
        var tagOutUrl = '{{ route('chart.tag.tag-expense', [$accountIds, $tagIds, $start->format('Ymd'), $end->format('Ymd')]) }}';
        var tagInUrl = '{{ route('chart.tag.tag-income', [$accountIds, $tagIds, $start->format('Ymd'), $end->format('Ymd')]) }}';
        var categoryOutUrl = '{{ route('chart.tag.category-expense', [$accountIds, $tagIds, $start->format('Ymd'), $end->format('Ymd')]) }}';
        var categoryInUrl = '{{ route('chart.tag.category-income', [$accountIds, $tagIds, $start->format('Ymd'), $end->format('Ymd')]) }}';
        var budgetsOutUrl = '{{ route('chart.tag.budget-expense', [$accountIds, $tagIds, $start->format('Ymd'), $end->format('Ymd')]) }}';
        var sourceOutUrl = '{{ route('chart.tag.source-expense', [$accountIds, $tagIds, $start->format('Ymd'), $end->format('Ymd')]) }}';
        var sourceInUrl = '{{ route('chart.tag.source-income', [$accountIds, $tagIds, $start->format('Ymd'), $end->format('Ymd')]) }}';
        var destOutUrl = '{{ route('chart.tag.dest-expense', [$accountIds, $tagIds, $start->format('Ymd'), $end->format('Ymd')]) }}';
        var destInUrl = '{{ route('chart.tag.dest-income', [$accountIds, $tagIds, $start->format('Ymd'), $end->format('Ymd')]) }}';

        var avgExpensesUrl = '{{ route('report-data.tag.avg-expenses', [$accountIds, $tagIds, $start->format('Ymd'), $end->format('Ymd')]) }}';
        var topExpensesUrl = '{{ route('report-data.tag.top-expenses', [$accountIds, $tagIds, $start->format('Ymd'), $end->format('Ymd')]) }}';
        var avgIncomeUrl = '{{ route('report-data.tag.avg-income', [$accountIds, $tagIds, $start->format('Ymd'), $end->format('Ymd')]) }}';
        var topIncomeUrl = '{{ route('report-data.tag.top-income', [$accountIds, $tagIds, $start->format('Ymd'), $end->format('Ymd')]) }}';

    </script>
    <script type="text/javascript" src="v1/js/ff/reports/tag/month.js?v={{ $FF_BUILD_TIME }}" nonce="{{ $JS_NONCE }}"></script>

@endsection

@section('styles')
    <link rel="stylesheet" href="v1/css/bootstrap-sortable.css?v={{ $FF_BUILD_TIME }}" type="text/css" media="all" nonce="{{ $JS_NONCE }}">
@endsection
