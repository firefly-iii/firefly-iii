@extends('layout.v3.session')
@section('content')
    TODO internals modal voor pagina settings
    TODO wizard modal voor weet ik veel
    TODO dark mode ook onthouden en dat script in het template
    TODO date picker
    TODO intro js

    <x-dashboard.boxes :start="$start" :end="$end" />

    <div class="row" x-data="index">
        <div class="col-lg-8 col-md-12 col-sm-12">
            <!--ACCOUNTS -->
            <div class="card card-primary card-outline mb-4">
                <div class="card-header with-border">
                    <div class="card-title"><a href="{{ route('accounts.index',['asset']) }}" title="{{ __('firefly.yourAccounts') }}">{{ __('firefly.yourAccounts') }}</a></div>
                </div>
                <div class="card-body">
                    <canvas id="accounts-chart" class="wide-chart"  height="400" width="100%"></canvas>
                </div>
                <div class="card-footer">
                    <a href="{{ route('accounts.index',['asset']) }}" class="btn btn-primary btn-sm"><span class="bi bi-cash"></span> {{ __('firefly.go_to_asset_accounts') }}</a>
                </div>
            </div>

            <!--BUDGETS -->
            <div class="card card-outline mb-4">
                <div class="card-header with-border">
                    <div class="card-title"><a href="{{ route('budgets.index') }}"
                                             title="{{ __('firefly.budgetsAndSpending') }}">{{ __('firefly.budgetsAndSpending') }}</a></div>
                </div>
                <div class="card-body">
                    <canvas id="budgets-chart" class="wide-chart" height="400" width="100%"></canvas>
                </div>
                <div class="card-footer">
                    <a href="{{ route('budgets.index') }}" class="btn btn-primary btn-sm">
                        <span class="bi bi-pie-chart"></span>
                        <span>{{ __('firefly.go_to_budgets') }}</span>
                    </a>
                </div>
            </div>
            <!--CATEGORIES -->
            <div class="card card-outline mb-4">
                <div class="card-header with-border">
                    <div class="card-title"><a href="{{ route('categories.index') }}"
                                             title="{{ __('firefly.categories') }}">{{ __('firefly.categories') }}</a></div>

                </div>
                <div class="card-body">
                    <canvas id="categories-chart" class="wide-chart" height="400" width="100%"></canvas>
                </div>
                <div class="card-footer">
                    <a href="{{ route('categories.index') }}" class="btn btn-primary btn-sm">
                        <span class="bi bi-bookmark"></span>
                        <span>{{ __('firefly.go_to_categories') }}</span>
                    </a>
                </div>
            </div>
        </div>
        <div class="col-lg-4 col-md-6 col-sm-12">

            <!--TRANSACTIONS -->
            <div id="all_transactions">
                @foreach($transactions as $data)
                <div class="card mb-4">
                    <div class="card-header with-border">
                        <div class="card-title"><a href="{{ route('accounts.show', [$data['account']['id']]) }}">{{ $data['account']['name'] }}</a>
                        </div>
                    </div>
                    @if(count($data['transactions']) > 0)
                    <div class="card-body p-0">
                        <x-lists.groups-tiny :transactions="$data['transactions']" />
                    </div>
                    @endif
                    @if(0 === count($data['transactions']))
                    <div class="card-body">
                        <p>
                            <em>
                                {{ __('firefly.no_transactions_account', ['name' => $data['account']['name']]) }}
                            </em>
                        </p>

                    </div>
                    @endif

                    <div class="card-footer">
                        <!-- Single button -->
                        <div class="btn-group" role="group">
                            <button type="button" class="btn btn-primary btn-sm dropdown-toggle" data-bs-toggle="dropdown" aria-expanded="false">{{ __('firefly.sidebar_frontpage_create') }}</button>
                            <ul class="dropdown-menu">
                                <li>
                                    <a class="dropdown-item" href="{{ route('transactions.create', ['withdrawal']) }}?source={{ $data['account']['id'] }}">{{ __('firefly.create_new_withdrawal') }}</a>
                                </li>
                                <li>
                                    <a class="dropdown-item" href="{{ route('transactions.create', ['deposit']) }}?destination={{ $data['account']['id'] }}">{{ __('firefly.create_new_deposit') }}</a>
                                </li>
                                <li>
                                    <a class="dropdown-item" href="{{ route('transactions.create', ['transfer']) }}?source={{ $data['account']['id'] }}">{{ __('firefly.create_new_transfer') }}</a>
                                </li>
                            </ul>
                        </div>

                        <div class="btn-group" role="group">
                            <button type="button" class="btn btn-primary btn-sm dropdown-toggle" data-bs-toggle="dropdown" aria-expanded="false">
                                 {{ bladeAccountBalance($data['account']) }}
                            </button>
                            <ul class="dropdown-menu">
                                <li>
                                    <a class="dropdown-item" href="{{ route('accounts.show', [$data['account']['id']]) }}">{{ __('firefly.show') }}</a>
                                </li>
                                <li>
                                    <a class="dropdown-item" href="{{ route('accounts.reconcile', [$data['account']['id']]) }}">{{ __('firefly.reconcile') }}</a>
                                </li>
                                <li>
                                    <a class="dropdown-item" href="{{ route('accounts.edit', [$data['account']['id']]) }}">{{ __('firefly.edit') }}</a>
                                </li>
                                <li>
                                    <a class="dropdown-item" href="{{ route('accounts.delete', [$data['account']['id']]) }}">{{ __('firefly.delete') }}</a>
                                </li>
                            </ul>
                        </div>
                    </div>
                </div>
                @endforeach
            </div>

            @if($billCount > 0)
            <!--BILLS -->
            <div class="card mb-4">
                <div class="card-header with-border">
                    <div class="card-title"><a href="{{ route('subscriptions.index') }}" title="{{ __('firefly.bills') }}">{{ __('firefly.bills') }}</a></div>

                </div>
                <div class="card-body">
                    <div class="center-chart">
                        <canvas id="bills-chart" class="low-chart" height="200"></canvas>
                    </div>
                </div>
                <div class="card-footer">
                    <a href="{{ route('bills.index') }}" class="btn btn-primary btn-sm"><span
                            class="bi bi-calendar"></span> {{ __('firefly.go_to_bills') }}</a>
                </div>
            </div>
            @endif

            <!--box for piggy bank data (JSON) -->
            <div id="piggy_bank_overview">

            </div>
        </div>
    </div>
    <div class="row">
        <div class="col-lg-12 col-md-12 col-sm-12">
            <!--EXPENSE ACCOUNTS -->
            <div class="card mb-4">
                <div class="card-header with-border">
                    <div class="card-title"><a href="{{ route('accounts.index',['expense']) }}" title="{{ __('firefly.expense_accounts') }}">{{ __('firefly.expense_accounts') }}</a>
                    </div>
                </div>
                <div class="card-body">
                    <canvas id="expense-accounts-chart" class="wide-chart" height="400"
                            width="100%"></canvas>
                </div>
                <div class="card-footer">
                    <a href="{{ route('accounts.index', ['expense']) }}" class="btn btn-primary btn-sm"><span
                            class="bi bi-cart"></span> {{ __('firefly.go_to_expense_accounts') }}</a>
                </div>
            </div>
            <!--OPTIONAL REVENUE ACCOUNTS -->
            <div class="card mb-4">
                <div class="card-header with-border">
                    <div class="card-title"><a href="{{ route('accounts.index',['revenue']) }}"
                                             title="{{ __('firefly.revenue_accounts') }}">{{ __('firefly.revenue_accounts') }}</a></div>

                </div>
                <div class="card-body">
                    <canvas id="revenue-accounts-chart" class="wide-chart" height="400"
                            width="100%"></canvas>
                </div>
                <div class="card-footer">
                    <a href="{{ route('accounts.index', ['revenue']) }}" class="btn btn-primary btn-sm"><span
                            class="bi bi-box-arrow-down"></span> {{ __('firefly.go_to_revenue_accounts') }}</a>
                </div>
            </div>
        </div>
    </div>

@endsection
@section('scripts')
    @vite(['js/pages/dashboard/dashboard.js'])


    <script type="text/javascript" nonce="{{ $JS_NONCE }}">
        var lineColor = 'red';
        var lineTextColor = '#000';
        if (window.matchMedia && window.matchMedia('(prefers-color-scheme: dark)').matches) {
            lineColor = '#a00';
            lineTextColor = '#bec5cb';
        }
        var billCount = {{ $billCount }};
        var accountFrontpageUrl = '{{ route('chart.account.frontpage') }}';
        var accountRevenueUrl = '{{ route('chart.account.revenue') }}';
        var accountExpenseUrl = '{{ route('chart.account.expense') }}';
        var piggyInfoUrl = '{{ route('json.fp.piggy-banks') }}';
        var drawVerticalLine = '';
        {{-- render vertical line with text "today"  --}}
        @if($start->lte($today) && $end->gte($today))
            drawVerticalLine = '{{ $today->isoFormat($monthAndDayFormat) }}';
        @endif
    </script>

    <script type="text/javascript" src="v1/js/lib/Chart.bundle.min.js?v={{ $FF_BUILD_TIME }}"
            nonce="{{ $JS_NONCE }}"></script>
    <script type="text/javascript" src="v1/js/lib/chartjs-plugin-annotation.min.js?v={{ $FF_BUILD_TIME }}"
            nonce="{{ $JS_NONCE }}"></script>
    <script type="text/javascript" src="v1/js/ff/charts.defaults.js?v={{ $FF_BUILD_TIME }}"
            nonce="{{ $JS_NONCE }}"></script>
    <script type="text/javascript" src="v1/js/ff/charts.js?v={{ $FF_BUILD_TIME }}" nonce="{{ $JS_NONCE }}"></script>
    <script type="text/javascript" src="v1/js/ff/index.js?v={{ $FF_BUILD_TIME }}" nonce="{{ $JS_NONCE }}"></script>

@endsection
    {{--
    <script type="text/javascript" nonce="{{ $JS_NONCE }}">
        var lineColor = 'red';
        var lineTextColor = '#000';
        if (window.matchMedia && window.matchMedia('(prefers-color-scheme: dark)').matches) {
            lineColor = '#a00';
            lineTextColor = '#bec5cb';
        }
        var billCount = {{ $billCount }};
        var accountFrontpageUrl = '{{ route('chart.account.frontpage') }}';
        var accountRevenueUrl = '{{ route('chart.account.revenue') }}';
        var accountExpenseUrl = '{{ route('chart.account.expense') }}';
        var piggyInfoUrl = '{{ route('json.fp.piggy-banks') }}';
        var drawVerticalLine = '';
        <!--render vertical line with text "today"  -->
        @if($start->lte($today) and $end->gte($today))
        drawVerticalLine = '{{ $today->isoFormat($monthAndDayFormat) }}';
        @endif
    </script>

    <script type="text/javascript" src="v1/js/lib/Chart.bundle.min.js?v={{ $FF_BUILD_TIME }}"
            nonce="{{ $JS_NONCE }}"></script>
    <script type="text/javascript" src="v1/js/lib/chartjs-plugin-annotation.min.js?v={{ $FF_BUILD_TIME }}"
            nonce="{{ $JS_NONCE }}"></script>
    <script type="text/javascript" src="v1/js/ff/charts.defaults.js?v={{ $FF_BUILD_TIME }}"
            nonce="{{ $JS_NONCE }}"></script>
    <script type="text/javascript" src="v1/js/ff/charts.js?v={{ $FF_BUILD_TIME }}" nonce="{{ $JS_NONCE }}"></script>
    <script type="text/javascript" src="v1/js/ff/index.js?v={{ $FF_BUILD_TIME }}" nonce="{{ $JS_NONCE }}"></script>
--}}
