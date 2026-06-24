@extends('layout.v3.session')
@section('content')
    <div class="row">
        <div class="@if($attachments->count() == 0)col-lg-12 col-md-12 col-sm-12 col-xs-12 @else col-lg-8 col-md-6 col-sm-12 col-xs-12 @endif ">
            <div class="card mb-2">
                <div class="card-header">
                    <div class="row">
                        <div class="col-9">
                            <h3 class="card-title">
                                @if(null !== ($budgetLimit ?? null))
                                    {{ trans('firefly.chart_budget_in_period', ['name' => $budget->name, 'start' => $budgetLimit->start_date->isoFormat($monthAndDayFormat), 'end' =>  $budgetLimit->end_date->isoFormat($monthAndDayFormat), 'currency' => $budgetLimit->transactionCurrency->name]) }}
                                @else
                                    {{ trans('firefly.chart_all_journals_for_budget', ['name' => $budget->name]) }}
                                @endif
                            </h3>
                        </div>
                        <div class="col text-end">
                            <button class="btn btn-sm btn-secondary-outline dropdown-toggle" type="button" id="card_header_menu" data-bs-toggle="dropdown" aria-expanded="false">
                                <span class="bi bi-list"></span>
                            </button>
                            <ul class="dropdown-menu" aria-labelledby="card_header_menu">
                                <li><a class="dropdown-item" href="{{ route('budgets.edit',$budget->id) }}"><span class="bi bi-pencil"></span> {{ __('firefly.edit') }}</a></li>
                                <li><a class="dropdown-item" href="{{ route('budgets.delete',$budget->id) }}"><span class="bi bi-trash"></span> {{ __('firefly.delete') }}</a></li>
                            </ul>
                        </div>
                    </div>
                </div>
                <div class="card-body">
                    <canvas id="budgetOverview" class="wide-chart" height="400" width="100%"></canvas>
                </div>
                @if(null !== ($budgetLimit ?? null))
                    <div class="card-footer">
                        <p class="text-muted">
                            {{ trans('firefly.chart_budget_in_period_only_currency', ['currency' => $budgetLimit->transactionCurrency->name]) }}
                        </p>
                    </div>
                @endif
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
    <div class="row">
        <div class="col-lg-4 col-md-6 col-sm-12 col-xs-12">
            <div class="card mb-2">
                <div class="card-header">
                    <h3 class="card-title">{{ __('firefly.expenses_by_category') }}</h3>
                </div>
                <div class="card-body">
                    <div class="center-chart">
                        <canvas id="budget-cat-out" class="medium-chart" height="250"></canvas>
                    </div>
                </div>
                @if(null !== ($budgetLimit ?? null))
                    <div class="card-footer">
                        <p class="text-muted">
                            {{ trans('firefly.chart_budget_in_period_only_currency', ['currency' => $budgetLimit->transactionCurrency->name]) }}
                        </p>
                    </div>
                @endif
            </div>
        </div>
        <div class="col-lg-4 col-md-6 col-sm-12 col-xs-12">
            <div class="card mb-2">
                <div class="card-header">
                    <h3 class="card-title">{{ __('firefly.expenses_by_asset_account') }}</h3>
                </div>
                <div class="card-body">
                    <div class="center-chart">
                        <canvas id="budget-asset-out" class="medium-chart" height="250"></canvas>
                    </div>
                </div>
                @if(null !== ($budgetLimit ?? null))
                    <div class="card-footer">
                        <p class="text-muted">
                            {{ trans('firefly.chart_budget_in_period_only_currency', ['currency' => $budgetLimit->transactionCurrency->name]) }}
                        </p>
                    </div>
                @endif
            </div>
        </div>
        <div class="col-lg-4 col-md-6 col-sm-12 col-xs-12">
            <div class="card mb-2">
                <div class="card-header">
                    <h3 class="card-title">{{ __('firefly.expenses_by_expense_account') }}</h3>
                </div>
                <div class="card-body">
                    <div class="center-chart">
                        <canvas id="budget-expense-out" class="medium-chart" height="250"></canvas>
                    </div>
                </div>
                @if(null !== ($budgetLimit ?? null))
                    <div class="card-footer">
                        <p class="text-muted">
                            {{ trans('firefly.chart_budget_in_period_only_currency', ['currency' => $budgetLimit->transactionCurrency->name]) }}
                        </p>
                    </div>
                @endif
            </div>
        </div>
    </div>
    @if(count($limits) > 0)
        <div class="row">
            <div class="offset-lg-9 col-lg-3 offset-md-9 col-md-3 col-sm-12 col-xs-12">
                <p class="small text-center"><a href="{{ route('budgets.show',$budget->id) }}">{{ __('firefly.showEverything') }}</a></p>
            </div>
        </div>
    @endif

    <div class="row">
        <div class="@if(count($limits) > 0) col-lg-9 col-md-9 col-sm-12 col-xs-12 @else col-lg-12 col-md-12 col-sm-12 col-xs-12 @endif ">

            <div class="card mb-2">
                <div class="card-header">
                    <h3 class="card-title">{{ __('firefly.transactions') }}</h3>
                </div>
                <div class="card-body">
                    <x-lists.groups-large :groups="$groups" />
                    @if(null !== ($budgetLimit ?? null))
                        <p>
                            <span class="bi bi-calendar"></span>
                            <a href="{{ route('budgets.show', [$budget->id]) }}">
                                {{ __('firefly.show_all_no_filter') }}
                            </a>
                        </p>
                    @endif
                </div>
            </div>
        </div>
        @if(count($limits) > 0)
            <div class="col-lg-3 col-md-3 col-sm-12 col-xs-12">
                @foreach($limits as $limit)
                    <div class="card @if($limit->start_date == $budgetLimit->start_date) card-primary card-outline @endif">
                        <div class="card-header">
                            <h3 class="card-title"><a href="{{ route('budgets.show.limit',[$budget->id,$limit->id]) }}">{{ $limit->start_date->isoFormat($monthAndDayFormat) }} &mdash;{{ $limit->end_date->isoFormat($monthAndDayFormat) }}
                                </a>
                            </h3>
                        </div>
                        <div class="card-body p-0">
                            <table class="table table-hover">
                                <tr>
                                    <td class="third">{{ __('firefly.amount') }}</td>
                                    <td>
                                        {!! format_amount_by_symbol($limit->amount, $limit->transactionCurrency->symbol, $limit->transactionCurrency->decimal_places) !!}
                                        @if($convertToPrimary && null !== $limit->native_amount)
                                            ({!! format_amount_by_symbol($limit->native_amount, $primaryCurrency->symbol, $primaryCurrency->decimal_places) !!})
                                        @endif
                                    </td>
                                </tr>
                                <tr>
                                    <td class="third">{{ __('firefly.spent') }}</td>
                                    <td>
                                        @if($convertToPrimary)
                                            {!! format_amount_by_symbol($limit->spent, $limit->transactionCurrency->symbol, $limit->transactionCurrency->decimal_places) !!}
                                            @if($limit->pc_spent)
                                                ({!! format_amount_by_symbol($limit->pc_spent, $primaryCurrency->symbol, $primaryCurrency->decimal_places) !!})
                                            @endif
                                        @else
                                            {!! format_amount_by_symbol($limit->spent, $limit->transactionCurrency->symbol, $limit->transactionCurrency->decimal_places) !!}
                                        @endif
                                    </td>
                                </tr>
                                @if(0 !==  bccomp('0', $limit->spent))
                                    <tr>
                                        <td colspan="2">
                                            @php
                                                $overspent = $limit->amount + $limit->spent < 0;
                                            @endphp
                                            @if($overspent)
                                                @php
                                                    // must have -1 here
                                                    $pct = ($limit->spent != 0 ? ($limit->amount / ($limit->spent*-1))*100 : 0);
                                                @endphp

                                                <div class="progress progress-striped">
                                                    <div class="progress-bar progress-bar-warning w-{{ round($pct) }}" role="progressbar" aria-valuenow="{{ round($pct) }}"
                                                         aria-valuemin="0"
                                                         aria-valuemax="100"></div>
                                                    <div class="progress-bar progress-bar-danger w-{{ round(100-$pct) }}" role="progressbar" aria-valuenow="{{ round(100-$pct) }}"
                                                         aria-valuemin="0" aria-valuemax="100"></div>
                                                </div>
                                            @else
                                                @php
                                                // must have -1 here
                                                $pct = ($limit->amount != 0 ? ((($limit->spent*-1) / $limit->amount)*100) : 0);
                                                @endphp

                                                <div class="progress progress-striped">
                                                    <div class="progress-bar progress-bar-success w-{{ round($pct) }}" role="progressbar" aria-valuenow="{{ round($pct) }}"
                                                         aria-valuemin="0"
                                                         aria-valuemax="100"></div>
                                                </div>
                                            @endif
                                        </td>
                                    </tr>
                                @endif
                            </table>
                        </div>
                    </div>
                @endforeach
                <p class="small text-center"><a href="{{ route('budgets.show',$budget->id) }}">{{ __('firefly.showEverything') }}</a></p>
            </div>
        @endif
    </div>

@endsection
@section('scripts')
    @vite(['js/pages/generic.js'])
    <script type="text/javascript" nonce="{{ $JS_NONCE }}">
        var budgetID = {{ $budget->id }};
        var budgetLimitID = 0;
        @if($budgetLimit?->id)
            budgetLimitID = {{ $budgetLimit->id }};
            var budgetChartUrl = '{{ route('chart.budget.budget-limit', [$budget->id, $budgetLimit->id] ) }}';
            var currencySymbol = '{{ $currencySymbol }}';
            var expenseCategoryUrl = '{{ route('chart.budget.expense-category', [$budget->id, $budgetLimit->id]) }}';
            var expenseAssetUrl = '{{ route('chart.budget.expense-asset', [$budget->id, $budgetLimit->id]) }}';
            var expenseExpenseUrl = '{{ route('chart.budget.expense-expense', [$budget->id, $budgetLimit->id]) }}';
        @else
            var budgetChartUrl = '{{ route('chart.budget.budget', [$budget->id] ) }}';
            var expenseCategoryUrl = '{{ route('chart.budget.expense-category', [$budget->id]) }}';
            var expenseAssetUrl = '{{ route('chart.budget.expense-asset', [$budget->id]) }}';
            var expenseExpenseUrl = '{{ route('chart.budget.expense-expense', [$budget->id]) }}';
        @endif
    </script>

    <script type="text/javascript" src="v1/js/lib/Chart.bundle.min.js?v={{ $FF_BUILD_TIME }}" nonce="{{ $JS_NONCE }}"></script>
    <script type="text/javascript" src="v1/js/ff/charts.defaults.js?v={{ $FF_BUILD_TIME }}" nonce="{{ $JS_NONCE }}"></script>
    <script type="text/javascript" src="v1/js/ff/charts.js?v={{ $FF_BUILD_TIME }}" nonce="{{ $JS_NONCE }}"></script>
    <script type="text/javascript" src="v1/js/ff/budgets/show.js?v={{ $FF_BUILD_TIME }}" nonce="{{ $JS_NONCE }}"></script>
    {{-- required for groups.twig --}}
    <script type="text/javascript" src="v1/js/ff/list/groups.js?v={{ $FF_BUILD_TIME }}" nonce="{{ $JS_NONCE }}"></script>
@endsection
