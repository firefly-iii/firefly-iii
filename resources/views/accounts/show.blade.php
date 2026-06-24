@extends('layout.v3.session')
@section('content')

    <div class="row">
        <div class="@if(0 === $attachments->count())col-lg-12 col-md-12 col-sm-12 col-xs-12@else col-lg-8 col-md-6 col-sm-12 col-xs-12 @endif ">
            <div class="card mb-2">
                <div class="card-header">
                    <div class="row">
                        <div class="col">
                            <h3 class="card-title">
                                @if($balances['balance'])
                                    <span title="balance">
                        {!! trans('firefly.chart_account_in_period', [
                                'balance' => format_amount_by_symbol($balances['balance'], $currency->symbol, $currency->decimal_places, true),
                                'name' => e($account->name),
                                'start' => $start->isoFormat($monthAndDayFormat),
                                'end' => $end->isoFormat($monthAndDayFormat)])  !!}
                                </span>
                                @elseif($balances['pc_balance'])
                                    <span title="pc_balance">
                            {!!  trans('firefly.chart_account_in_period', [
                                'balance' => format_amount_by_symbol($balances['pc_balance'], $primaryCurrency->symbol, $primaryCurrency->decimal_places, true),
                                'name' => e($account->name),
                                'start' => $start->isoFormat($monthAndDayFormat),
                                'end' => $end->isoFormat($monthAndDayFormat)])  !!}
                                </span>
                                @endif
                            </h3>
                        </div>
                        <div class="col text-end">
                            <div class="dropdown">
                                <button class="btn btn-sm btn-secondary-outline dropdown-toggle" type="button" id="card_header_menu" data-bs-toggle="dropdown" aria-expanded="false">
                                    <span class="bi bi-list"></span>
                                </button>
                                <ul class="dropdown-menu" aria-labelledby="card_header_menu">
                                        <li><a class="dropdown-item" href="{{ route('accounts.edit', [$account->id]) }}"><span
                                                    class="bi bi-pencil"></span> {{ __('firefly.edit') }}</a></li>
                                        <li><a class="dropdown-item" href="{{ route('accounts.delete', [$account->id]) }}"><span
                                                    class="bi bi-trash"></span> {{ __('firefly.delete') }}</a></li>
                                        <li>
                                            <a class="dropdown-item" href="{{ route('accounts.reconcile', [$account->id]) }}"><span class="bi bi-check"></span> {{ __('firefly.reconcile_this_account') }}</a>
                                        </li>
                                    </ul>
                                </div>
                        </div>
                    </div>

                </div>
                <div class="card-body">
                    <div>
                        <canvas id="overview-chart" class="wide-chart" height="400" width="100%"></canvas>
                    </div>
                </div>
            </div>
        </div>
        @if($attachments->count() > 0)
            <div class="col-lg-4 col-md-6 col-sm-12 col-xs-12">
                <div class="card mb-2">
                    <div class="card-header">
                        <h3 class="card-title">
                            {{ __('firefly.attachments') }}
                        </h3>
                    </div>
                    <div class="card-body p-0">
                        {% include 'list.attachments' %}
                    </div>
                </div>
            </div>
        @endif
    </div>
    @if(!$showAll && $isLiability)
        <div class="row">
            <div class="col-lg-4 col-md-6 col-sm-12 col-xs-12">
                <div class="card mb-2">
                    <div class="card-header">
                        <h3 class="card-title">pay-off by date</h3>
                    </div>
                    <div class="card-body">
                        Content
                    </div>
                </div>
            </div>
        </div>
    @endif
    @if(!$showAll && !$isLiability)
        <div class="row">
            <div class="col-lg-4 col-md-6 col-sm-12 col-xs-12">
                <div class="card mb-2">
                    <div class="card-header">
                        <h3 class="card-title">{{ __('firefly.expenses_by_category') }}</h3>
                    </div>
                    <div class="card-body">
                        <div class="center-chart">
                            <canvas id="account-cat-out" class="medium-chart" height="250"></canvas>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-lg-4 col-md-6 col-sm-12 col-xs-12">
                <div class="card mb-2">
                    <div class="card-header">
                        <h3 class="card-title">{{ __('firefly.expenses_by_budget') }}</h3>
                    </div>
                    <div class="card-body">
                        <div class="center-chart">
                            <canvas id="account-budget-out" class="medium-chart" height="250"></canvas>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-lg-4 col-md-6 col-sm-12 col-xs-12">
                <div class="card mb-2">
                    <div class="card-header">
                        <h3 class="card-title">{{ __('firefly.income_by_category') }}</h3>
                    </div>
                    <div class="card-body">
                        <div class="center-chart">
                            <canvas id="account-cat-in" class="medium-chart" height="250"></canvas>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    @endif

    <div class="row">
        @if($location)
            <div class="col-lg-6">
                <div class="card mb-2">
                    <div class="card-header">
                        <h3 class="card-title">{{ __('firefly.location') }}</h3>
                    </div>
                    <div class="card-body">
                        <div id="location_map" class="map-size"></div>
                    </div>
                </div>
            </div>
        @endif
        @if(1 === $account->notes->count())
            <div class="col-lg-6">
                <div class="card mb-2">
                    <div class="card-header">
                        <h3 class="card-title">{{ __('firefly.notes') }}</h3>
                    </div>
                    <div class="card-body">
                        {{ parse_markdown($account->notes->first()->text) }}
                    </div>
                </div>
            </div>
        @endif
    </div>

    <div class="row">
        <div class="@if(count($periods) > 0)col-lg-10 col-md-8 col-sm-12 @else col-lg-12 col-md-12 col-sm-12 @endif ">
            <div class="card mb-2">
                <div class="card-header">
                    <h3 class="card-title">{{ __('firefly.transactions') }}
                        @if($balances['balance'])
                            ({!! format_amount_by_symbol($balances['balance'], $currency->symbol, $currency->decimal_places, true)  !!})
                        @elseif($balances['pc_balance'])
                            ({!! format_amount_by_symbol($balances['pc_balance'], $primaryCurrency->symbol, $primaryCurrency->decimal_places, true)  !!})
                        @endif
                    </h3>
                </div>
                <div class="card-body">
                    @php
                        $showReconcile = false;
                    @endphp

                    @if('Asset account' === $account->accountType->type)
                        @php
                            $showReconcile = true;
                        @endphp
                    @endif

                    <x-lists.groups-large :groups="$groups" :account="$account" />

                    <p>
                        <span class="bi bi-calendar"></span>
                        @if(count($periods) > 0)
                            <a href="{{ route('accounts.show.all', [$account->id]) }}">
                                {{ __('firefly.show_all_no_filter') }}
                            </a>
                        @else
                            <a href="{{ route('accounts.show', [$account->id]) }}">
                                {{ __('firefly.show_the_current_period_and_overview') }}
                            </a>
                        @endif
                    </p>
                </div>
            </div>
        </div>
        @if(count($periods) > 0)
            <div class="col-lg-2 col-md-4 col-sm-12 col-xs-12">
                <x-lists.periods :periods="$periods" />
            </div>
        @endif
    </div>
@endsection

@section('scripts')
    @vite(['js/pages/generic.js'])
    <script type="text/javascript" nonce="{{ $JS_NONCE }}">
        // location stuff
        @if($location)
            var latitude = {{ $location['latitude'] ?? '52.3167' }};
            var longitude = {{ $location['longitude'] ?? '5.5500' }};
            var zoomLevel = {{ $location['zoom_level'] ?? '6' }};
        @endif

        var showAll = true;
        currencySymbol = "{{ $currency->symbol }}";
        var accountID = {{ $account->id }};
        var chartUrl = '{{ $chartUrl }}';
        @if(!$showAll)
            showAll = false;
            // url's for charts:

            var incomeCategoryUrl = '{{ route('chart.account.income-category', [$account->id, $start->format('Ymd'), $end->format('Ymd')]) }}';
            var expenseCategoryUrl = '{{ route('chart.account.expense-category', [$account->id, $start->format('Ymd'), $end->format('Ymd')]) }}';
            var expenseBudgetUrl = '{{ route('chart.account.expense-budget', [$account->id, $start->format('Ymd'), $end->format('Ymd')]) }}';
            var drawVerticalLine = '';
            var lineColor = 'red';
            var lineTextColor = '#000';
            if (window.matchMedia && window.matchMedia('(prefers-color-scheme: dark)').matches) {
                lineColor = '#a00';
                lineTextColor = '#bec5cb';
            }
            {{-- render vertical line with text "today" --}}
            @if($start->lte($today) && $end->gte($today))
                drawVerticalLine = '{{ $today->isoFormat($monthAndDayFormat) }}';
            @endif
        @endif

    </script>
    @if($location)
        <script src="v1/lib/leaflet/leaflet.js?v={{ $FF_BUILD_TIME }}" nonce="{{ $JS_NONCE }}"></script>
    @endif
    <script type="text/javascript" src="v1/js/lib/Chart.bundle.min.js?v={{ $FF_BUILD_TIME }}"
            nonce="{{ $JS_NONCE }}"></script>
    <script type="text/javascript" src="v1/js/lib/chartjs-plugin-annotation.min.js?v={{ $FF_BUILD_TIME }}"
            nonce="{{ $JS_NONCE }}"></script>
    <script type="text/javascript" src="v1/js/ff/charts.defaults.js?v={{ $FF_BUILD_TIME }}" nonce="{{ $JS_NONCE }}"></script>
    <script type="text/javascript" src="v1/js/ff/charts.js?v={{ $FF_BUILD_TIME }}" nonce="{{ $JS_NONCE }}"></script>

    <script src="v1/js/lib/jquery-ui.min.js?v={{ $FF_BUILD_TIME }}" type="text/javascript" nonce="{{ $JS_NONCE }}"></script>
    <script src="v1/js/lib/jquery.color-2.1.2.min.js?v={{ $FF_BUILD_TIME }}" type="text/javascript"
            nonce="{{ $JS_NONCE }}"></script>
    <script src="v1/js/ff/accounts/show.js?v={{ $FF_BUILD_TIME }}" type="text/javascript" nonce="{{ $JS_NONCE }}"></script>
    {{--  required for groups.twig --}}
    <script type="text/javascript" src="v1/js/ff/list/groups.js?v={{ $FF_BUILD_TIME }}" nonce="{{ $JS_NONCE }}"></script>
@endsection

@section('styles')
    @if($location)
        <link rel="stylesheet" href="v1/lib/leaflet/leaflet.css?v={{ $FF_BUILD_TIME }}" nonce="{{ $JS_NONCE }}">
    @endif
@endsection
