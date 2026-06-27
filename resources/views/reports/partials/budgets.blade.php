<table class="table table-hover sortable">
    <thead>
    <tr>
        <th data-defaultsign="az">{{ __('firefly.budget') }}</th>
        <th data-defaultsign="month" class="hidden-xs">{{ __('firefly.date') }}</th>
        <th data-defaultsign="_19"  class="text-end hidden-xs">{{ __('firefly.budgeted') }}</th>
        <th data-defaultsign="_19" class="hidden-xs">{{ trans('list.percentage') }}</th>
        <th data-defaultsign="_19" class="text-end">{{ __('firefly.spent') }}</th>
        <th data-defaultsign="_19" class="hidden-xs">{{ trans('list.percentage') }}</th>
        <th data-defaultsort="disabled" class="hidden-xs">&nbsp;</th>
        <th data-defaultsign="_19" class="text-end hidden-xs">{{ __('firefly.left') }}</th>
        <th data-defaultsign="_19" class="text-end">{{ __('firefly.overspent') }}</th>
    </tr>
    </thead>
    <tbody>
    @foreach($report['budgets'] as $budget)
        @foreach($budget['budget_limits'] as $budgetLimit)
            <tr>
                @if($budget['no_budget'])
                    <td data-value="zzz">
                        <em>{{ __('firefly.no_budget') }} ({{ $budgetLimit['currency_name'] }})</em>
                    </td>
                @else
                    <td data-value="{{ $budget['budget_name'] }}">
                        <a href="{{ route('budgets.show', [$budget['budget_id']]) }}">{{ $budget['budget_name'] }}</a>
                    </td>
                @endif
                <!-- date, hidden on mobile  -->
                <td class="hidden-xs" data-value="{{ $budgetLimit['start_date']->format('Y-m-d') }}">
                    @if(null !== $budgetLimit['budget_limit_id'])
                        <a href="{{ route('budgets.show.limit', [$budget['budget_id'], $budgetLimit['budget_limit_id']]) }}">
                            {{ $budgetLimit['start_date']->isoFormat($monthAndDayFormat) }}
                            &mdash;
                            {{ $budgetLimit['end_date']->isoFormat($monthAndDayFormat) }}
                        </a>
                    @endif
                </td>

                <!-- budgeted, hidden on mobile -->
                <td data-value="{{ $budgetLimit['budgeted'] }}" class="text-end hidden-xs">
                    @if(null !== $budgetLimit['budgeted'])
                        {!! format_amount_by_symbol($budgetLimit['budgeted'], $budgetLimit['currency_symbol'], $budgetLimit['currency_decimal_places']) !!}
                    @endif
                </td>
                <!-- percentage, hidden -->
                <td data-value="{{ $budgetLimit['budgeted_pct'] }}" class="hidden-xs">
                    {{ $budgetLimit['budgeted_pct'] }}%
                </td>


                <!-- spent, visible on mobile -->
                <td data-value="{{ $budgetLimit['spent'] }}" class="text-end">
                    {!! format_amount_by_symbol($budgetLimit['spent'], $budgetLimit['currency_symbol'], $budgetLimit['currency_decimal_places']) !!}
                </td>
                <!-- percentage, hidden -->
                <td data-value="{{ $budgetLimit['spent_pct'] }}" class="hidden-xs">
                    {{ $budgetLimit['spent_pct'] }}%
                </td>

                <!-- info button, not visible on mobile -->
                <td class="hidden-xs">
                    @if($budgetLimit['spent'] != 0)
                        <span class="bi bi-info-circle text-muted firefly-info-button"
                           data-location="budget-spent-amount" data-currency-id="{{ $budgetLimit['currency_id'] }}" data-budget-id=" @if('' === $budget['budget_id'])@else{{ $budget['budget_id'] }}@endif"></span>
                    @endif
                </td>


                <!-- left, hidden on mobile  -->
                <td data-value="{{ $budgetLimit['left'] }}" class="text-end hidden-xs">
                    @if(null !== $budgetLimit['left'])
                        {!! format_amount_by_symbol($budgetLimit['left'], $budgetLimit['currency_symbol'], $budgetLimit['currency_decimal_places']) !!}
                    @endif
                </td>
                <!-- overspent, visible. -->
                <td data-value="{{ $budgetLimit['overspent'] }}" class="text-end">
                    @if(null !== $budgetLimit['overspent'])
                        {!! format_amount_by_symbol($budgetLimit['overspent'], $budgetLimit['currency_symbol'], $budgetLimit['currency_decimal_places']) !!}
                    @endif
                </td>
            </tr>
        @endforeach
    @endforeach
    </tbody>
    <tfoot>
    @foreach($report['sums'] as $sum)
        <tr>
            <td colspan="2"><em>{{ __('firefly.sum') }} ({{ $sum['currency_name'] }})</em></td>
            <td class="text-end">{!! format_amount_by_symbol($sum['budgeted'], $sum['currency_symbol'], $sum['currency_decimal_places']) !!}</td>
            <td>&nbsp;</td>
            <td class="text-end">{!! format_amount_by_symbol($sum['spent'], $sum['currency_symbol'], $sum['currency_decimal_places']) !!}</td>
            <td>&nbsp;</td>
            <td>&nbsp;</td>
            <td class="text-end">{!! format_amount_by_symbol($sum['left'], $sum['currency_symbol'], $sum['currency_decimal_places']) !!}</td>
            <td class="text-end">{!! format_amount_by_symbol($sum['overspent'], $sum['currency_symbol'], $sum['currency_decimal_places']) !!}</td>
        </tr>
        @endforeach
    </tfoot>
</table>
