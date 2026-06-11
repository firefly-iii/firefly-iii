@extends('layout.v3.session')
@section('content')
    <!-- date selector -->
    <div class="row">
        <div class="col-lg-12 col-md-12 col-sm-12 col-xs-12">
            <div class="card mb-2">
                <div class="card-header">
                    <h3 class="card-title">{{ __('firefly.budget_period_navigator') }}</h3>
                </div>
                <div class="card-body">
                    <div class="row" id="periodNavigator">
                        <div class="col-lg-2 col-md-4 col-sm-12 col-xs-12">
                            <select class="form-control selectPeriod" name="previous">
                                <option label="{{ __('firefly.select_date') }}" value="x">{{ __('firefly.select_date') }}</option>
                                @foreach($prevLoop as $array)
                                    <option label="{{ $array['title'] }}" value="{{ $array['label'] }}" data-start="{{ $array['start']->format('Y-m-d') }}"
                                            data-end="{{ $array['end']->format('Y-m-d') }}">{{ $array['title'] }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-lg-8 col-md-4 col-sm-12 col-xs-12 text-center">
                            <div class="btn-group btn-group-lg pt-0">
                                <a href="{{ route('budgets.index', [$prevLoop[0]['start']->format('Y-m-d'), $prevLoop[0]['end']->format('Y-m-d')]) }}"
                                   class="btn btn-secondary" title="{{ $prevLoop[0]['title'] }}">&larr;</a>
                                <a title="{{ $start->isoFormat($monthAndDayFormat) }} - {{ $end->isoFormat($monthAndDayFormat) }}"
                                   href="{{ route('budgets.index', [$start->format('Y-m-d'), $end->format('Y-m-d')]) }}"
                                   class="btn btn-secondary">{{ $periodTitle }}</a>
                                <a href="{{ route('budgets.index', [$nextLoop[0]['start']->format('Y-m-d'), $nextLoop[0]['end']->format('Y-m-d')]) }}"
                                   class="btn btn-secondary" title="{{ $nextLoop[0]['title'] }}">&rarr;</a>
                            </div>
                        </div>
                        <div class="col-lg-2 col-md-4 col-sm-12 col-xs-12 text-end">
                            <select class="form-control selectPeriod" name="next">
                                <option label="{{ __('firefly.select_date') }}" value="x">{{ __('firefly.select_date') }}</option>
                                @foreach($nextLoop as $array)
                                    <option label="{{ $array['title'] }}" value="{{ $array['label'] }}" data-start="{{ $array['start']->format('Y-m-d') }}"
                                            data-end="{{ $array['end']->format('Y-m-d') }}">{{ $array['title'] }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <!-- available budget configurator. -->
    @if(0 === count($availableBudgets))
        <div class="row">
            <div class="col-lg-12 col-md-12 col-sm-12 col-xs-12">
                <div class="card mb-2">
                    <div class="card-header">
                        <h3 class="card-title">
                            {{ trans('firefly.total_available_budget_in_currency', ['currency' => $primaryCurrency->name]) }}
                            <br>
                            <small>{{ trans('firefly.between_dates_breadcrumb', ['start' => $start->isoFormat($monthAndDayFormat), 'end' => $end->isoFormat($monthAndDayFormat)]) }}</small>
                        </h3>
                    </div>
                    <div class="card-body">
                        <div class="row mb-1">
                            {{--= info about the amount budgeted --}}
                            <div class="col-lg-3 col-md-3 col-sm-3 col-xs-3">
                                <small>{{ __('firefly.budgeted') }} ({{ __('firefly.see_below') }}):
                                    <span class="budgeted_amount" data-value="{{ $budgeted }}" data-id="0" data-currency="{{ $primaryCurrency['id'] }}">
                                        {!! \FireflyIII\Support\Facades\Steam::formatAmountBySymbol($budgeted, $primaryCurrency->symbol, $primaryCurrency->decimal_places) !!}
                                    </span>
                                </small>
                            </div>
                            {{-- info about the amount spent --}}
                            <div class="col-lg-9 col-md-9 col-sm-9 col-xs-9 text-end">
                                <small class="available_bar" data-id="0">{{ trans('firefly.available_between', ['start' => $start->isoFormat($monthAndDayFormat), 'end' => $end->isoFormat($monthAndDayFormat)]) }}:
                                    <span class="available_amount" data-id="0" data-value="0" data-currency="{{ $primaryCurrency['id'] }}" data-value="0">{!! \FireflyIII\Support\Facades\Steam::formatAmountBySymbol(0, $primaryCurrency->symbol, $primaryCurrency->decimal_places, true) !!}</span>
                                </small>
                            </div>
                        </div>
                        {{-- info text to show how much is spent (in currency). --}}
                        <div class="row spentInfo" data-id="0" data-value="{{ $spent }}">
                            <div class="col-lg-12 col-md-12 col-sm-12 col-xs-12">
                                <small>{{ trans('firefly.spent_between', ['start' => $start->isoFormat($monthAndDayFormat), 'end' => $end->isoFormat($monthAndDayFormat)]) }}:
                                    {!! \FireflyIII\Support\Facades\Steam::formatAmountBySymbol($spent, $primaryCurrency->symbol, $primaryCurrency->decimal_places) !!} </small>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    @endif

    @php
    $boxSize = count($availableBudgets) > 1 ? 6 : 12;
    @endphp
    @if(count($availableBudgets) > 0)
        <div class="row">
            @foreach($availableBudgets as $budget)
                <div class="col-lg-{{ $boxSize }} col-md-12 col-sm-12 col-xs-12">
                    <div class="card mb-2">
                        <div class="card-header">
                            <h3 class="card-title">
                                {{ trans('firefly.total_available_budget_in_currency', ['currency' => $budget['transaction_currency']['name']]) }}
                                <br>
                                <small>{{ trans('firefly.between_dates_breadcrumb', ['start' => $budget['start_date']->isoFormat($monthAndDayFormat), 'end' => $budget['end_date']->isoFormat($monthAndDayFormat)]) }}</small>
                            </h3>
                        </div>
                        <div class="card-body">
                            <div class="row">
                                {{-- info about the amount budgeted --}}
                                <div class="col-lg-3 col-md-3 col-sm-3 col-xs-3">
                                    <small>{{ __('firefly.budgeted') }}:
                                        <span class="text-success money-positive budgeted_amount" data-id="{{ $budget['id'] }}" data-currency="{{ $budget['transaction_currency']['id'] }}">
                                        {!!  \FireflyIII\Support\Facades\Steam::formatAmountBySymbol($budget['budgeted'], $budget['transaction_currency']['symbol'], $budget['transaction_currency']['decimal_places'], false)  !!}
                                            @if(null !== $budget['pc_budgeted'])
                                            ({!! \FireflyIII\Support\Facades\Steam::formatAmountBySymbol($budget['pc_budgeted'], $primaryCurrency->symbol, $primaryCurrency->decimal_places, false)  !!})
                                            @endif
                                    </span>
                                    </small>
                                </div>
                                {{--  info about the amount spent --}}
                                <div class="col-lg-9 col-md-9 col-sm-9 col-xs-9 text-end mb-1">
                                    <small class="available_bar" data-id="{{ $budget['id'] }}">{{ trans('firefly.available_between', ['start' => $budget['start_date']->isoFormat($monthAndDayFormat), 'end' => $budget['end_date']->isoFormat($monthAndDayFormat)]) }}:
                                        <span class="available_amount" data-id="{{ $budget['id'] }}" data-currency="{{ $budget['transaction_currency']['id'] }}"
                                              data-value="{{ $budget['amount'] }}">
                                            {!! \FireflyIII\Support\Facades\Steam::formatAmountBySymbol($budget['amount'], $budget['transaction_currency']['symbol'], $budget['transaction_currency']['decimal_places'], true) !!}
                                        @if($convertToPrimary && 0 !== $budget->pc_amount)
                                                ({!!  \FireflyIII\Support\Facades\Steam::formatAmountBySymbol($budget->pc_amount, $primaryCurrency->symbol, $primaryCurrency->decimal_places, true)  !!})
                                            @endif
                                        </span>
                                    </small>
                                </div>
                            </div>
                            {{-- progress bar to visualise available vs budgeted. --}}
                            <div class="row">
                                <div class="col-lg-12 col-md-12 col-sm-12 col-xs-12">
                                    <div class="progress budgeted_bar" data-id="{{ $budget['id'] }}" data-budgeted="{{ $budget['budgeted'] }}"
                                         data-available="{{ $budget['amount'] }}" data-currency="{{ $budget['transaction_currency']['id'] }}">
                                        {{-- red: the exact amount of the available budget, if more has budgeted. --}}
                                        <div class="progress-bar progress-bar-danger" data-id="{{ $budget['id'] }}" role="progressbar" aria-valuenow="10"
                                             aria-valuemin="0"
                                             aria-valuemax="100"></div>

                                        {{-- orange: overbudgeted amount --}}
                                        <div class="progress-bar progress-bar-warning" data-id="{{ $budget['id'] }}" role="progressbar" aria-valuenow="0"
                                             aria-valuemin="0"
                                             aria-valuemax="100"></div>

                                        {{-- budgeted amount if enough or les --}}
                                        <div class="progress-bar progress-bar-info" data-id="{{ $budget['id'] }}" role="progressbar" aria-valuenow="0"
                                             aria-valuemin="0"
                                             aria-valuemax="100"></div>
                                    </div>
                                </div>
                            </div>
                            {{--  info text to show how much is spent (in currency). --}}
                            <div class="row spentInfo" data-id="{{ $budget['id'] }}">
                                <div class="col-lg-12 col-md-12 col-sm-12 col-xs-12">
                                    <small>{!! trans('firefly.spent_between_left', [
                                            'start' => $budget['start_date']->isoFormat($monthAndDayFormat),
                                            'end' => $budget['end_date']->isoFormat($monthAndDayFormat),
                                            'spent' => \FireflyIII\Support\Facades\Steam::formatAmountBySymbol($budget['spent'], $budget['transaction_currency']['symbol'], $budget['transaction_currency']['decimal_places']),
                                            'left' => \FireflyIII\Support\Facades\Steam::formatAmountBySymbol($budget['budgeted'] + $budget['spent'], $budget['transaction_currency']['symbol'], $budget['transaction_currency']['decimal_places']),
                                        ])  !!}
                                        </small>
                                </div>
                            </div>

                            {{-- bar to visualise spending in budget. --}}
                            <div class="row">
                                <div class="col-lg-12 col-md-12 col-sm-12 col-xs-12">
                                    <div class="progress spent_bar" data-id="{{ $budget['id'] }}" data-budgeted="{{ $budget['budgeted'] }}"
                                         data-spent="{{ $budget['spent'] }}">
                                        <div class="progress-bar progress-bar-warning" role="progressbar" aria-valuenow="0" aria-valuemin="0"
                                             aria-valuemax="100"></div>
                                        <div class="progress-bar progress-bar-danger" role="progressbar" aria-valuenow="0" aria-valuemin="0" aria-valuemax="100"></div>
                                        <div class="progress-bar progress-bar-info" role="progressbar" aria-valuenow="0" aria-valuemin="0" aria-valuemax="100"></div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            @endforeach
        </div>
    @endif
    @if(0 === count($budgets) && 0 === count($inactive))
        TODO TODO TODO TODO
        {% include 'partials.empty' with {objectType: 'default', type: 'budgets',route: route('budgets.create')} %}
        {# make FF ignore demo for now. #}
        {% set shownDemo = true %}
    @else
        <div class="row">
            <div class="col-lg-12 col-md-12 col-sm-12 col-xs-12">
                <div class="card mb-2">
                    <div class="card-header">
                        <div class="row">
                            <div class="col">
                                <h3 class="card-title">{{ __('firefly.budgets') }}</h3>
                            </div>
                            <div class="col text-end">
                                <div class="dropdown">
                                    <button class="btn btn-sm btn-secondary dropdown-toggle" type="button" id="dropdownMenuButton1" data-bs-toggle="dropdown" aria-expanded="false">
                                        <span class="bi bi-list"></span>
                                    </button>
                                    <ul class="dropdown-menu" aria-labelledby="dropdownMenuButton1">
                                        <li><a class="dropdown-item" href="{{ route('budgets.create') }}"><span
                                                    class="bi bi-plus-circle"></span> {{ __('firefly.createBudget') }}
                                            </a></li>
                                    </ul>
                                </div>
                            </div>
                        </div>

                    </div>
                    <div class="card-body p-0">
                        <table class="table table-bordered sortable-table table-striped sortable" id="budgetList">
                            <thead>
                            <tr>
                                <th class="hidden-sm hidden-xs ten">&nbsp;</th>
                                <th>{{ __('firefly.budget') }}</th>
                                <th class="quarter">{{ __('firefly.budgeted') }}</th>
                                <th class="hidden-sm hidden-xs">{{ __('firefly.spent') }} ({{  strtolower(__('firefly.per_day')) }})</th>
                                <th>{{ __('firefly.left') }} ({{ strtolower(__('firefly.per_day')) }})</th>
                            </tr>
                            </thead>
                            <tbody>
                            {{-- {# START OF BUDGET ROW #} --}}

                            @foreach($budgets as $budget)
                                <tr data-id="{{ $budget['id'] }}">
                                    <td class="hidden-sm hidden-xs">
                                        <div class="btn-group btn-group-sm">
                                            <a href="#" class="handle object-handle btn btn-secondary"><span class="bi bi-list"></span></a>
                                            <a href="{{ route('budgets.edit', $budget['id']) }}" class="btn btn-xs btn-primary"><span class="bi bi-pencil"></span></a>
                                            <a href="{{ route('budgets.delete', $budget['id']) }}" class="btn btn-xs btn-danger"><span class="bi bi-trash"></span></a>
                                        </div>
                                    </td>
                                    <td>
                                        @if(0 === count($budget['budgeted']))
                                            <a href="{{ route('budgets.show', $budget['id']) }}" data-id="{{ $budget['id'] }}">{{ $budget['name'] }}</a>
                                        @endif
                                        @if(1 === count($budget['budgeted']))
                                            @foreach($budget['budgeted'] as $budgetLimit)
                                                <a href="{{ route('budgets.show.limit', [$budget['id'], $budgetLimit['id']]) }}" data-id="{{ $budget['id'] }}">{{ $budget['name'] }}</a>
                                            @endforeach
                                        @endif

                                        @if(count($budget['budgeted']) > 1)
                                            @foreach($budget['budgeted'] as $budgetLimit)
                                                <a href="{{ route('budgets.show.limit', [$budget['id'], $budgetLimit['id']]) }}" data-id="{{ $budget['id'] }}">{{ $budget['name'] }} ({{ $budgetLimit['currency_name'] }})</a><br>
                                            @endforeach
                                        @endif
                                        @if(null !== $budget['auto_budget'])
                                            @if(1 === $budget['auto_budget']['auto_budget_type'])
                                                <span class="bi bi-calendar-check" title="{{ __('firefly.auto_budget_reset_icon') }}"></span>
                                            @endif
                                            @if(2 === $budget['auto_budget']['auto_budget_type'])
                                                <span class="bi bi-calendar-plus" title="{{ __('firefly.auto_budget_rollover_icon') }}"></span>
                                            @endif
                                            @if(3 === $budget['auto_budget']['auto_budget_type'])
                                                <span class="bi bi-calendar-plus" title="{{ __('firefly.auto_budget_adjusted_icon') }}"></span>
                                            @endif
                                        @endif
                                        @if(count($budget['attachments']) > 0)
                                            <span class="bi bi-paperclip"></span>
                                        @endif
                                    </td>
                                    <td>
                                        @if(0 === count($budget['budgeted']))
                                            <div class="input-group">
                                                <input type="hidden" name="balance_currency_id" value="{{ $primaryCurrency['id'] }}"/>
                                                <span class="input-group-text" id="budgeted_{{ $budget['id'] }}">{{ $primaryCurrency->symbol }}</span>

                                                @if(!$anonymous)
                                                    <input aria-describedby="budgeted_{{ $budget['id'] }}" class="form-control budget_amount" data-original="0" data-id="{{ $budget['id'] }}" data-currency="{{ $primaryCurrency['id'] }}" data-limit="0" value="0" autocomplete="off" min="0" name="amount" type="number">
                                                @endif
                                                @if($anonymous)
                                                    <input disabled readonly aria-describedby="budgeted_{{ $budget['id'] }}" class="form-control" data-original="0" data-id="0" data-currency="{{ $primaryCurrency['id'] }}" data-limit="0" value="0" autocomplete="off" min="0" name="amount" type="number">
                                                @endif
                                                @if(count($budget['budgeted']) < count($currencies))
                                                <button class="btn btn-outline-secondary dropdown-toggle" type="button" data-bs-toggle="dropdown" aria-expanded="false"><em class="bi bi-caret-down"></em></button>
                                                <ul class="dropdown-menu dropdown-menu-end">
                                                    <li>
                                                        <a href="#" class="dropdown-item create_bl" data-id="{{ $budget['id'] }}">
                                                            <span class="bi bi-plus-circle"></span>
                                                            {{ __('firefly.add_budget_limit_currency') }}
                                                        </a>
                                                        </li>
                                                </ul>
                                                @endif
                                            </div>

                                            <span class="hidden text-danger budget_warning" data-id="{{ $budget['id'] }}"></span>
                                        @endif
                                        @if(count($budget['budgeted'])>0)
                                            @foreach($budget['budgeted'] as $budgetLimit)
                                                @if(!$budgetLimit['in_range'])
                                                    <small class="text-muted">
                                                        {{ trans('firefly.budget_limit_not_in_range', ['start' => $budgetLimit['start_date'], 'end' => $budgetLimit['end_date']]) }}
                                                    </small><br>
                                                @endif
                                                    <div class="input-group mb-1">
                                                        <span class="input-group-text">{{ $budgetLimit['currency_symbol'] }}</span>
                                                        @if(!$anonymous)
                                                            <input class="form-control budget_amount" data-original="{{ $budgetLimit['amount'] }}"
                                                                   data-id="{{ $budget['id'] }}" data-limit="{{ $budgetLimit['id'] }}" value="{{ $budgetLimit['amount'] }}"
                                                                   autocomplete="off"
                                                                   min="0" name="amount" type="number">
                                                        @endif
                                                        @if($anonymous)
                                                            <input disabled readonly class="form-control budget_amount" data-original="0" data-id="0" data-limit="0" value="0" autocomplete="off" min="0" name="amount" type="number">
                                                        @endif
                                                        <button class="btn btn-outline-secondary dropdown-toggle" type="button" data-bs-toggle="dropdown" aria-expanded="false"><em class="bi bi-caret-down"></em></button>
                                                        <ul class="dropdown-menu dropdown-menu-end">
                                                            <li>
                                                                <a class="dropdown-item delete_bl" href="#" data-budget-limit-id="{{ $budgetLimit['id'] }}">
                                                                    <em title="{{ __('firefly.remove_budgeted_amount') }}" class="bi bi-trash" aria-hidden="true"></em>
                                                                    {{ trans('firefly.remove_budgeted_amount', ['currency' => $budgetLimit['currency_name']]) }}</a>
                                                            </li>
                                                            @if(count($budget['budgeted']) < count($currencies))
                                                                <li>
                                                                    <a href="#" class="dropdown-item create_bl" data-id="{{ $budget['id'] }}">
                                                                        <span class="bi bi-plus-circle"></span>
                                                                        {{ __('firefly.add_budget_limit_currency') }}
                                                                    </a>
                                                                </li>
                                                            @endif
                                                            <li>
                                                                <a class="dropdown-item edit_bl" href="#" data-id="{{ $budgetLimit['id'] }}">
                                                                    <em title="{{ __('firefly.edit_bl_notes') }}" class="bi bi-pencil" aria-hidden="true"></em>
                                                                    {{ trans('firefly.edit_bl_notes') }}</a>
                                                            </li>
                                                            @if('' !== $budgetLimit['notes'])
                                                                <li>
                                                                    <a href="#" class="dropdown-item show_bl" data-id="{{ $budgetLimit['id'] }}">
                                                                        <em title="{{ __('firefly.view_notes') }}" class="bi bi-chat-dots" aria-hidden="true"></em> {{ __('firefly.view_notes') }}</a>
                                                                </li>
                                                            @endif

                                                        </ul>
                                                    </div>
                                                <span class="hidden text-danger budget_warning" data-id="{{ $budget['id'] }}" data-budgetLimit="{{ $budgetLimit['id'] }}"></span>
                                            @endforeach
                                        @endif
                                    </td>
                                    <td class="hidden-sm hidden-xs spent text-end" data-id="{{ $budget['id'] }}">
                                        {{-- this is spent in budget limits: --}}
                                        @foreach($budget['budgeted'] as $budgetLimit)
                                        <span title="{{ __('firefly.spent_this_period') }}">{!! \FireflyIII\Support\Facades\Steam::formatAmountBySymbol($budgetLimit['spent'], $budgetLimit['currency_symbol'], $budgetLimit['currency_decimal_places'])  !!}</span>
                                        @if(0 === $budgetLimit['active_days_passed'])
                                            <span title="{{ trans('firefly.spent_this_period_per_day', ['days' => 0]) }}">({!! \FireflyIII\Support\Facades\Steam::formatAmountBySymbol($budgetLimit['spent'], $budgetLimit['currency_symbol'], $budgetLimit['currency_decimal_places'])  !!})</span>
                                        @else
                                            <span title="{{ trans('firefly.spent_this_period_per_day', ['days' => $budgetLimit['active_days_passed']]) }}">({!! \FireflyIII\Support\Facades\Steam::formatAmountBySymbol($budgetLimit['spent'] / $budgetLimit['active_days_passed'], $budgetLimit['currency_symbol'], $budgetLimit['currency_decimal_places'])  !!})</span>
                                        @endif
                                        <br />
                                        @endforeach

                                        {{-- this is spent NOT in budget limits: --}}
                                        @foreach($budget['spent'] as $spent)
                                        @if(0 !== bccomp('0', $spent['spent_outside']))
                                            <span title="{{ __('firefly.spent_in_budget_limit_outside_period') }}">{!! \FireflyIII\Support\Facades\Steam::formatAmountBySymbol($spent['spent_outside'], $spent['currency_symbol'], $spent['currency_decimal_places']) !!}</span>
                                        @if(0 === $activeDaysPassed)
                                            <span title="{{ trans('firefly.spent_in_budget_limit_outside_period_per_day', ['days' => 0]) }}">({!!  \FireflyIII\Support\Facades\Steam::formatAmountBySymbol($spent['spent_outside'], $spent['currency_symbol'], $spent['currency_decimal_places'])  !!})</span>
                                        @else
                                            <span title="{{ trans('firefly.spent_in_budget_limit_outside_period_per_day', ['days' => $activeDaysPassed]) }}">({!! \FireflyIII\Support\Facades\Steam::formatAmountBySymbol($spent['spent_outside'] / $activeDaysPassed, $spent['currency_symbol'], $spent['currency_decimal_places'])  !!})</span>
                                        @endif
                                        <br />
                                        @endif
                                        @endforeach

                                    </td>
                                    {{-- this cell displays the amount left in the budget, per budget limit. --}}
                                    <td data-id="{{ $budget['id'] }}" class="text-end left">
                                        {{-- The amount left can only be shown for actual budget limits. --}}
                                        @foreach($budget['budgeted'] as $budgetLimit)
                                            <span data-currency="{{ $budgetLimit['currency_id'] }}" data-limit="{{ $budgetLimit['id'] }}" data-value="{{ $budgetLimit['left'] }}" class="left_span amount_left">
                                                {{-- the amount left --}}
                                                <span title="{{ __('firefly.left_in_budget_limit_overview') }}">{!!  \FireflyIII\Support\Facades\Steam::formatAmountBySymbol($budgetLimit['left'], $budgetLimit['currency_symbol'], $budgetLimit['currency_decimal_places'])  !!}</span>
                                                {{-- if the budget limit is in the past, this is not interesting. --}}
                                                {{-- if there is nothing left, this is not interesting. --}}
                                                @if(!$budgetLimit['in_past'] && -1 === bccomp('0', $budgetLimit['left']))
                                                    @if(0 === $budgetLimit['active_days_left'])
                                                        <span title="{{ trans('firefly.left_in_budget_limit_per_day', ['days' => 0]) }}">({!! \FireflyIII\Support\Facades\Steam::formatAmountBySymbol($budgetLimit['left'], $budgetLimit['currency_symbol'], $budgetLimit['currency_decimal_places'])  !!})
                                                    @else
                                                        <span title="{{ trans('firefly.left_in_budget_limit_per_day', ['days' => $budgetLimit['active_days_left']]) }}">({!! \FireflyIII\Support\Facades\Steam::formatAmountBySymbol($budgetLimit['left'] / $budgetLimit['active_days_left'], $budgetLimit['currency_symbol'], $budgetLimit['currency_decimal_places'])  !!})</span>
                                                    @endif
                                                @endif
                                                {{-- if there is nothing left, just format 0.00 --}}
                                                @if(!$budgetLimit['in_past'] && -1 !== bccomp('0', $budgetLimit['left']))
                                                    <span title="{{ trans('firefly.nothing_left_in_budget') }}">({!! \FireflyIII\Support\Facades\Steam::formatAmountBySymbol('0', $budgetLimit['currency_symbol'], $budgetLimit['currency_decimal_places'])  !!})</span>
                                                @endif
                                            </span><br />
                                        @endforeach
                                    </td>
                                </tr>
                            @endforeach
                            {{--  END OF BUDGET ROW  --}}
                            </tbody>
                        </table>
                    </div>
                    <div class="card-footer">
                        <div class="col text-end">
                            <div class="dropdown">
                                <button class="btn btn-sm btn-secondary dropdown-toggle" type="button" id="dropdownMenuButton1" data-bs-toggle="dropdown" aria-expanded="false">
                                    <span class="bi bi-list"></span>
                                </button>
                                <ul class="dropdown-menu" aria-labelledby="dropdownMenuButton1">
                                    <li><a class="dropdown-item" href="{{ route('budgets.create') }}"><span
                                                class="bi bi-plus-circle"></span> {{ __('firefly.createBudget') }}
                                        </a></li>
                                </ul>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>


        <div class="row">
            <div class="col-lg-3 col-md-4 col-sm-12 col-xs-12">
                <div class="card mb-2">
                    <div class="card-header with-border">
                        <h3 class="card-title">{{ __('firefly.transactionsWithoutBudget') }}</h3>
                    </div>
                    <div class="card-body">
                        <p>
                            <a href="{{ route('budgets.no-budget', [$start->format('Y-m-d'), $end->format('Y-m-d')]) }}">
                                {{ trans('firefly.transactions_no_budget', ['start' => $start->isoFormat($monthAndDayFormat), 'end' => $end->isoFormat($monthAndDayFormat)]) }}
                            </a>
                        </p>
                    </div>
                </div>
            </div>
            @if($paginator ?? false && $paginator->count() > 0 && count($inactive) > 0)
                <div class="col-lg-3 col-md-4 col-sm-12 col-xs-12">
                    <div class="card mb-2" id="createBudgetBox">
                        <div class="card-header with-border">
                            <h3 class="card-title">{{ __('firefly.createBudget') }}</h3>
                        </div>
                        <div class="card-body">
                            <a href="{{ route('budgets.create') }}" class="btn btn-success pull-right">{{ __('firefly.createBudget') }}</a>
                        </div>
                    </div>
                </div>
            @endif
            @if(count($inactive) > 0)
                <div class="col-lg-3 col-sm-4 col-md-6">
                    <div class="card">
                        <div class="card-header with-border">
                            <h3 class="card-title">{{ __('firefly.inactiveBudgets') }}</h3>
                        </div>
                        <div class="card-body">
                            @foreach($inactive as $budget)
                                @if($loop->index + 1 === count($inactive))
                                    <a href="{{ route('budgets.show',$budget['id']) }}">{{ $budget['name'] }}</a>
                                @else
                                    <a href="{{ route('budgets.show',$budget['id']) }}">{{ $budget['name'] }}</a>,
                                @endif
                            @endforeach
                        </div>
                    </div>
                </div>
            @endif
        </div>
    @endif


@endsection
@section('styles')
    <link href="v1/css/bootstrap-sortable.css?v={{ $FF_BUILD_TIME }}" type="text/css" rel="stylesheet" media="all" nonce="{{ $JS_NONCE }}">
@endsection

@section('scripts')
    @vite(['js/pages/budgets/index.js'])

    <script src="v1/js/lib/jquery-ui.min.js?v={{ $FF_BUILD_TIME }}" type="text/javascript" nonce="{{ $JS_NONCE }}"></script>
    <script type="text/javascript" nonce="{{ $JS_NONCE }}">

        // index route.
        var budgetIndexUrl = "{{ route('budgets.index',['START','END']) }}";

        // budget limit create form.
        var createBudgetLimitUrl = "{{ route('budget-limits.create', ['REPLACEME', $start->format('Y-m-d'), $end->format('Y-m-d')]) }}";
        var storeBudgetLimitUrl = "{{ route('budget-limits.store') }}";
        var updateBudgetLimitUrl = "{{ route('budget-limits.update', ['REPLACEME']) }}";
        var showBudgetLimitUrl = "{{ route('budget-limits.show', ['REPLACEME']) }}";
        var editBudgetLimitUrl = "{{ route('budget-limits.edit', ['REPLACEME']) }}";
        var deleteBudgetLimitUrl = "{{ route('budget-limits.delete', ['REPLACEME']) }}";
        var totalBudgetedUrl = "{{ route('json.budget.total-budgeted', ['REPLACEME', $start->format('Y-m-d'), $end->format('Y-m-d')]) }}";

        // period thing:
        var periodStart = "{{ $start->format('Y-m-d') }}";
        var periodEnd = "{{ $end->format('Y-m-d') }}";
    </script>
    <script type="text/javascript" src="v1/js/lib/bootstrap-sortable.js?v={{ $FF_BUILD_TIME }}" nonce="{{ $JS_NONCE }}"></script>
    <script type="text/javascript" src="v1/js/ff/budgets/index.js?v={{ $FF_BUILD_TIME }}" nonce="{{ $JS_NONCE }}"></script>
@endsection
