@extends('layout.v3.session')
@section('content')
    TODO boxes
    TODO charts
    TODO piggy bank overview
    TODO side menu voor het maken van shit
    TODO internals modal voor pagina settings
    TODO wizard modal voor weet ik veel
    TODO dark mode ook onthouden en dat script in het template
    TODO fix create menu.

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
                <div class="box-body">
                    <canvas id="budgets-chart" class="wide-chart" height="400" width="100%"></canvas>
                </div>
                <div class="box-footer">
                    <a href="{{ route('budgets.index') }}" class="btn btn-primary btn-sm">
                        <span class="fa fa-pie-chart"></span>
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
                <div class="box-body">
                    <canvas id="categories-chart" class="wide-chart" height="400" width="100%"></canvas>
                </div>
                <div class="box-footer">
                    <a href="{{ route('categories.index') }}" class="btn btn-primary btn-sm">
                        <span class="fa fa-bookmark"></span>
                        <span>{{ __('firefly.go_to_categories') }}</span>
                    </a>
                </div>
            </div>
        </div>
        <div class="col-lg-4 col-md-6 col-sm-12">

            <!--TRANSACTIONS -->
            <div id="all_transactions">
                @foreach($transactions as $data)
                <div class="card">
                    <div class="box-header with-border">
                        <div class="card-title"><a
                                href="{{ route('accounts.show', [$data['account']['id']]) }}">{{ $data['account']['name'] }}</a>
                        </div>
                    </div>
                    @if(count($data['transactions']) > 0)
                    <div class="box-body no-padding">
                        <x-lists.groups-tiny></x-lists.groups-tiny>
                        <!-- {% include 'list.groups-tiny' with {'transactions': data.transactions,'account': data.account} %} -->
                    </div>
                    @endif
                    @if(0 === count($data['transactions']))
                    <div class="box-body">
                        <p>
                            <em>
                                {{ __('firefly.no_transactions_account', ['name' => $data['account']['name']]) }}
                            </em>
                        </p>

                    </div>
                    @endif

                    <div class="box-footer clearfix">
                        <!-- Single button -->
                        <div class="btn-group">
                            <a type="button" class="btn btn-success dropdown-toggle" data-toggle="dropdown"
                               aria-haspopup="true" aria-expanded="false">
                                {{ __('firefly.sidebar_frontpage_create') }} <span class="caret"></span>
                            </a>
                            <ul class="dropdown-menu">
                                <li>
                                    <a href="{{ route('transactions.create', ['withdrawal']) }}?source={{ $data['account']['id'] }}">{{ __('firefly.create_new_withdrawal') }}</a>
                                </li>
                                <li>
                                    <a href="{{ route('transactions.create', ['deposit']) }}?destination={{ $data['account']['id'] }}">{{ __('firefly.create_new_deposit') }}</a>
                                </li>
                                <li>
                                    <a href="{{ route('transactions.create', ['transfer']) }}?source={{ $data['account']['id'] }}">{{ __('firefly.create_new_transfer') }}</a>
                                </li>
                            </ul>
                        </div>
                        <div class="btn-group">
                            <a type="button" class="btn btn-default dropdown-toggle" data-toggle="dropdown"
                               aria-haspopup="true" aria-expanded="false"
                               href="{{ route('accounts.show', [$data['account']['id']]) }}"> TODO account balance: @{{ data.account|balance }}
                                <span class="caret"></span>
                            </a>
                            <ul class="dropdown-menu">
                                <li>
                                    <a href="{{ route('accounts.show', [$data['account']['id']]) }}">{{ __('firefly.show') }}</a>
                                </li>
                                <li>
                                    <a href="{{ route('accounts.reconcile', [$data['account']['id']]) }}">{{ __('firefly.reconcile') }}</a>
                                </li>
                                <li>
                                    <a href="{{ route('accounts.edit', [$data['account']['id']]) }}">{{ __('firefly.edit') }}</a>
                                </li>
                                <li>
                                    <a href="{{ route('accounts.delete', [$data['account']['id']]) }}">{{ __('firefly.delete') }}</a>
                                </li>
                            </ul>
                        </div>
                    </div>
                </div>
                @endforeach
            </div>

            @if($billCount > 0)
            <!--BILLS -->
            <div class="card">
                <div class="box-header with-border">
                    <div class="card-title"><a href="{{ route('subscriptions.index') }}"
                                             title="{{ __('firefly.bills') }}">{{ __('firefly.bills') }}</a></div>

                </div>
                <div class="box-body">
                    <div class="center-chart">
                        <canvas id="bills-chart" class="low-chart" height="200"></canvas>
                    </div>
                </div>
                <div class="box-footer">
                    <a href="{{ route('bills.index') }}" class="btn btn-primary btn-sm"><span
                            class="fa fa-calendar"></span> {{ __('firefly.go_to_bills') }}</a>
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
            <div class="card">
                <div class="box-header with-border">
                    <div class="card-title"><a href="{{ route('accounts.index',['expense']) }}"
                                             title="{{ __('firefly.expense_accounts') }}">{{ __('firefly.expense_accounts') }}</a>
                    </div>
                </div>
                <div class="box-body">
                    <canvas id="expense-accounts-chart" class="wide-chart" height="400"
                            width="100%"></canvas>
                </div>
                <div class="box-footer">
                    <a href="{{ route('accounts.index', ['expense']) }}" class="btn btn-primary btn-sm"><span
                            class="fa fa-shopping-cart"></span> {{ __('firefly.go_to_expense_accounts') }}</a>
                </div>
            </div>
            <!--OPTIONAL REVENUE ACCOUNTS -->
            <div class="card">
                <div class="box-header with-border">
                    <div class="card-title"><a href="{{ route('accounts.index',['revenue']) }}"
                                             title="{{ __('firefly.revenue_accounts') }}">{{ __('firefly.revenue_accounts') }}</a></div>

                </div>
                <div class="box-body">
                    <canvas id="revenue-accounts-chart" class="wide-chart" height="400"
                            width="100%"></canvas>
                </div>
                <div class="box-footer">
                    <a href="{{ route('accounts.index', ['revenue']) }}" class="btn btn-primary btn-sm"><span
                            class="fa fa-download"></span> {{ __('firefly.go_to_revenue_accounts') }}</a>
                </div>
            </div>
        </div>
    </div>

@endsection
@section('scripts')
    @vite(['js/pages/dashboard/dashboard.js'])
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
