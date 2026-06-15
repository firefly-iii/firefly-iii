@extends('layout.v3.session')
@section('content')

    {{-- upper show-all instruction --}}
    @if(count($periods) > 0)
        <div class="row">
            {{-- for withdrawals, deposits and transfers --}}
            <div class="col-lg-4 col-md-6 col-sm-12 col-xs-12">
                <div class="card mb-2">
                    <div class="card-header">
                        <h3 class="card-title">{{ __('firefly.categories') }}</h3>
                    </div>
                    <div class="card-body">
                        <canvas id="category_chart" class="medium-chart" height="250"></canvas>
                    </div>
                </div>
            </div>
            {{-- only for withdrawals --}}
            @if('withdrawal' === $objectType)
                <div class="col-lg-4 col-md-6 col-sm-12 col-xs-12">
                    <div class="card mb-2">
                        <div class="card-header">
                            <h3 class="card-title">{{ __('firefly.budgets') }}</h3>
                        </div>
                        <div class="card-body">
                            <canvas id="budget_chart" class="medium-chart" height="250"></canvas>
                        </div>
                    </div>
                </div>
            @endif
            @if('withdrawal' !== $objectType)
                <div class="col-lg-4 col-md-6 col-sm-12 col-xs-12">
                    <div class="card mb-2">
                        <div class="card-header with-border">
                            <h3 class="card-title">{{ __('firefly.all_source_accounts') }}</h3>
                        </div>
                        <div class="card-body">
                            <canvas id="source_chart" class="medium-chart" height="250"></canvas>
                        </div>
                    </div>
                </div>
            @endif
            {{-- for all --}}
            <div class="col-lg-4 col-md-6 col-sm-12 col-xs-12">
                <div class="card mb-2">
                    <div class="card-header with-border">
                        <h3 class="card-title">{{ __('firefly.all_destination_accounts') }}</h3>
                    </div>
                    <div class="card-body">
                        <canvas id="destination_chart" class="medium-chart" height="250"></canvas>
                    </div>
                </div>
            </div>
        </div>
        <div class="row">
            <div class="offset-lg-10 col-lg-2 offset-md-10 col-md-2 col-sm-12 col-xs-12">
                <p class="small text-center"><a href="{{ route('transactions.index.all',[$objectType]) }}">{{ __('firefly.showEverything') }}</a></p>
            </div>
        </div>
    @endif


    {{-- list with journals --}}
    <div class="row">
        <div class="@if(count($periods) > 0) col-lg-10 col-md-10 col-sm-12 @else col-lg-12 col-md-12 col-sm-12 @endif">
            <div class="card mb-2">
                <div class="card-header with-border">
                    <div class="row">
                        <div class="col">
                            <h3 class="card-title">{{ $subTitle }}</h3>
                        </div>
                        <div class="col text-end">
                            <div class="dropdown">
                                <button class="btn btn-sm btn-secondary dropdown-toggle" type="button" id="dropdownMenuButton1" data-bs-toggle="dropdown" aria-expanded="false">
                                    <span class="bi bi-list"></span>
                                </button>
                                <ul class="dropdown-menu" aria-labelledby="dropdownMenuButton1">
                                    <li><a class="dropdown-item" href="{{ route('transactions.create', [$objectType]) }}"><span
                                                class="bi bi-plus-circle"></span> {{ __('firefly.create_new_transaction') }}
                                        </a></li>
                                </ul>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="card-body p-0">
                    <x-lists.groups-large :groups="$groups" :account="null" />
                </div>
                <div class="card-footer">
                    {{-- links for other views --}}
                    @if(count($periods) > 0)
                        <p>
                            <span class="bi bi-calendar"></span>
                            <a href="{{ route('transactions.index.all', [$objectType]) }}">{{ __('firefly.show_all_no_filter') }}</a>
                        </p>
                    @else
                        <p>
                            <span class="bi bi-calendar"></span>
                            <a href="{{ route('transactions.index', [$objectType]) }}">{{ __('firefly.show_the_current_period_and_overview') }}</a>
                        </p>
                    @endif
                </div>
            </div>
        </div>

        {{-- boxes with info --}}
        @if(count($periods) > 0)
            <div class="col-lg-2 col-md-2 col-sm-12 col-xs-12">
                <x-lists.periods :periods="$periods" />
            </div>
        @endif

    </div>

    {{-- lower show-all instruction  --}}
    @if(count($periods) > 0)
        <div class="row">
            <div class="offset-lg-10 col-lg-2 offset-md-10 col-md-2 col-sm-12 col-xs-12">
                <p class="small text-center"><a href="{{ route('transactions.index.all', [$objectType]) }}">{{ __('firefly.showEverything') }}</a></p>
            </div>
        </div>
        @endif

@endsection
@section('scripts')
    @vite(['js/pages/transactions/index.js'])
    {{--  required for groups.twig --}}
    <script type="text/javascript" nonce="{{ $JS_NONCE }}">
        var showAll = @if(count($periods) > 0) false @else true @endif;
        var cloneGroupUrl = '{{ route('transactions.clone') }}';
        var cloneAndEditUrl = '{{ route('transactions.clone') }}?redirect=edit';
        var categoryChartUrl = '{{ route('chart.transactions.categories', [$objectType, $start->format('Y-m-d'), $end->format('Y-m-d')]) }}';
        var budgetChartUrl = '{{ route('chart.transactions.budgets', [$start->format('Y-m-d'), $end->format('Y-m-d')]) }}';
        var destinationChartUrl = '{{ route('chart.transactions.destinationAccounts', [$objectType, $start->format('Y-m-d'), $end->format('Y-m-d')]) }}';
        var sourceChartUrl = '{{ route('chart.transactions.sourceAccounts', [$objectType, $start->format('Y-m-d'), $end->format('Y-m-d')]) }}';

    </script>
    <script type="text/javascript" src="v1/js/lib/Chart.bundle.min.js?v={{ $FF_BUILD_TIME }}" nonce="{{ $JS_NONCE }}"></script>
    <script type="text/javascript" src="v1/js/lib/chartjs-plugin-annotation.min.js?v={{ $FF_BUILD_TIME }}" nonce="{{ $JS_NONCE }}"></script>
    <script type="text/javascript" src="v1/js/ff/charts.defaults.js?v={{ $FF_BUILD_TIME }}" nonce="{{ $JS_NONCE }}"></script>
    <script type="text/javascript" src="v1/js/ff/charts.js?v={{ $FF_BUILD_TIME }}" nonce="{{ $JS_NONCE }}"></script>

    <script type="text/javascript" src="v1/js/ff/transactions/index.js?v={{ $FF_BUILD_TIME }}" nonce="{{ $JS_NONCE }}"></script>
    <script type="text/javascript" src="v1/js/ff/list/groups.js?v={{ $FF_BUILD_TIME }}" nonce="{{ $JS_NONCE }}"></script>

@endsection
