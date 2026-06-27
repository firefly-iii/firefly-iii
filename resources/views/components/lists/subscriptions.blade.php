@foreach($bills as $objectGroupOrder => $objectGroup)
<h5 class="m-3">{{ $objectGroup['object_group_title'] }}</h5>

<table class="table table-hover table-sm table-bordered" id="bill-sortable">
    <thead class="table-secondary">
    <tr>
        <th class="hidden-sm hidden-xs">&nbsp;</th>
        <th class="hidden-sm hidden-xs">&nbsp;</th>
        <th>{{ __('list.name') }}</th>
        <th class="hidden-sm hidden-md hidden-xs">{{ __('list.linked_to_rules') }}</th>
        <th class="text-end">{{ trans('list.matchingAmount') }}</th>
        <th class="hidden-sm hidden-xs">{{ trans('list.paid_current_period') }}</th>
        <th class="hidden-sm hidden-xs">{{ trans('list.next_expected_match') }}</th>
        <th class="hidden-sm hidden-xs">{{ trans('list.repeat_freq') }}</th>
    </tr>
    </thead>
        @if(count($objectGroup['bills']) > 0)
        <tbody class="bill-connected-list" data-title="{{ $objectGroup['object_group_title'] }}">
    @foreach($objectGroup['bills'] as $entry)
    <tr class="bill-sortable" data-id="{{ $entry['id'] }}" data-name="{{ $entry['name'] }}" data-order="{{ $entry['order'] }}" data-position="{{ $loop->index }}">
        <td class="hidden-sm hidden-xs">
            <span class="bi bi-list bill-handle"></span>
        </td>
        <td class="hidden-sm hidden-xs">
            <div class="btn-group btn-group-sm edit_tr_buttons"><a href="{{ route('subscriptions.edit',$entry['id']) }}"
                                                                   class="btn btn-secondary"><span
                        class="bi bi-pencil"></span></a><a
                    href="{{ route('subscriptions.delete',$entry['id']) }}" class="btn btn-danger"><span
                        class="bi bi-trash"></span></a></div>
        </td>
        <td>
            @if(!$entry['active'])
                <span class="bi bi-ban"></span>
            @endif
            <a href="{{ route('subscriptions.show',$entry['id']) }}" title="{{ $entry['name'] }}">{{ $entry['name'] }}</a>
            {{-- count attachments --}}
            @if(count($entry['attachments']) > 0)
            <span class="bi bi-paperclip"></span>
            @endif

        </td>
        <td class="hidden-sm hidden-md hidden-xs rules">
            @if(count($entry['rules']) > 0)
            <ul class="list-unstyled">
                @foreach($entry['rules'] as $rule)
                <li>
                    <a href="{{ route('rules.edit', [$rule['id']]) }}">
                        {{ $rule['title'] }}
                    </a>
                    @if(!$rule['active'])({{ strtolower(__('firefly.list_inactive_rule')) }})@endif
                </li>
                @endforeach
            </ul>
            @endif
        </td>
        <td class="text-end">
                <span class="mr-2" title="{{ e(format_amount_by_symbol($entry['amount_min'], $entry['currency_symbol'], $entry['currency_decimal_places'], false)) }} -- {{ e(format_amount_by_symbol($entry['amount_max'], $entry['currency_symbol'], $entry['currency_decimal_places'], false)) }}">
                    ~ {!! format_amount_by_symbol(($entry['amount_max'] + $entry['amount_min'])/2, $entry['currency_symbol'], $entry['currency_decimal_places'])  !!}

                    @if('0' !== $entry['pc_amount_max'] && null !== $entry['pc_amount_max'])
                        (~ {!! format_amount_by_symbol(($entry['pc_amount_max'] + $entry['pc_amount_min'])/2, $primaryCurrency->symbol, $primaryCurrency->decimal_places) }})
                    @endif
                </span>
        </td>

        {{--
        paidDates = 0 (bill not paid in period)
        pay_dates  = 0 (bill not expected to be paid in this period)
        bill is active.
        --}}
        @if(0 === count($entry['paid_dates']) && 0 === count($entry['pay_dates']) && $entry['active'])
        <td class="paid_in_period text-muted">
            {{ trans('firefly.not_expected_period') }}
        </td>
        <td class="expected_in_period hidden-sm hidden-xs">
            @if($entry['next_expected_match'])
            {{ new \Carbon\Carbon($entry['next_expected_match'])->isoFormat($monthAndDayFormat) }}
            @endif
        </td>
        @endif

        {{--
        paid_dates = 0 (bill not paid in period)
        pay_dates  > 0 (bill IS expected to be paid in this period)
        bill is active
        first pay date is in the past.
        --}}
        @if(0 === count($entry['paid_dates']) && count($entry['pay_dates']) > 0 && $entry['active'])
           @if($entry['next_expected_match_diff'] === __('firefly.not_expected_period')) {{-- terrible code, you should sue me for this. --}}
        <td class="paid_in_period text-muted">
            {{ $entry['next_expected_match_diff'] }}
        </td>
        @else
        <td class="paid_in_period text-warning">
            {{ $entry['next_expected_match_diff'] }}
            <!-- {{ __('firefly.bill_expected_date', ['date' => $entry['next_expected_match_diff']]) }} -->
        </td>
        @endif
        <td class="expected_in_period hidden-sm hidden-xs">
            @foreach($entry['pay_dates'] as $date)
            {{ new \Carbon\Carbon($date)->isoFormat($monthAndDayFormat) }}<br>
            @endforeach

        </td>
        @endif

        {{--
        paid_dates >= 0 (bill is paid X times).
        Don't care about pay_dates.
        --}}

        @if(count($entry['paid_dates']) > 0 && $entry['active'])
        <td class="paid_in_period text-success">
            @foreach($entry['paid_dates'] as $currentPaid)
            <a href="{{ route('transactions.show', $currentPaid['transaction_group_id']) }}">
                {{ new \Carbon\Carbon($currentPaid['date'])->isoFormat($monthAndDayFormat) }}
            </a>
            <br/>
            @endforeach
        </td>
        <td class="expected_in_period hidden-sm hidden-xs">
            @if($entry['next_expected_match'])
            {{ new \Carbon\Carbon($entry['next_expected_match'])->isoFormat($monthAndDayFormat) }}
            @else
                <span class="text-muted">{{ $entry['next_expected_match_diff'] }}</span>
            @endif
        </td>
        @endif
        {{-- bill is not active --}}
        @if(!$entry['active'])
        <td class="paid_in_period text-muted">
            ~
        </td>
        <td class="expected_in_period text-muted hidden-sm hidden-xs">~</td>
        @endif
        <td class="hidden-sm hidden-xs">
            {{ __('firefly.repeat_freq_' . $entry['repeat_freq']) }}
            @if($entry['skip'] > 0)
            {{ __('firefly.skips_over') }} {{ $entry['skip'] }}
            @endif
            @if(null !== $entry['end_date'])
            <br>
            @if(new \Carbon\Carbon($entry['end_date'])->lte($today))
                <span class="text-danger">{{ trans('firefly.bill_end_index_line', ['date' => new \Carbon\Carbon($entry['end_date'])->isoFormat($monthAndDayFormat)])  }}</span>
            @else
                {{ trans('firefly.bill_end_index_line', ['date' => new \Carbon\Carbon($entry['end_date'])->isoFormat($monthAndDayFormat)])  }}
            @endif
            @endif
            @if($entry['extension_date'])
            <br>
                @if(new \Carbon\Carbon($entry['extension_date'])->lte($today))
            <span class="text-danger">{{ trans('firefly.bill_extension_index_line', ['date' => new \Carbon\Carbon($entry['extension_date'])->isoFormat($monthAndDayFormat)])  }}</span>
            @else
            {{ trans('firefly.bill_extension_index_line', ['date' => new \Carbon\Carbon($entry['extension_date'])->isoFormat($monthAndDayFormat)])  }}
            @endif
            @endif
        </td>
    </tr>
    @endforeach
    @foreach($sums[$objectGroupOrder] as $sum)
        @if('0' !== $sum['avg'])
    <tr>
        <td class="hidden-sm hidden-xs" colspan="2">&nbsp;</td> <!-- handle -->
        <td colspan="2" class="text-end"> <!-- title -->
            <small>{{ __('firefly.sum') }} ({{ $sum['currency_name'] }}) ({{ __('firefly.active_exp_bills_only') }})</small>
        </td>
        <td class="text-end"> <!-- amount -->
            {!! format_amount_by_symbol($sum['avg'], $sum['currency_symbol'], $sum['currency_decimal_places']) !!}
        </td>
        <td class="hidden-sm hidden-xs" colspan="3">&nbsp;</td> <!-- handle -->
    </tr>
    @endif
        @if('0' !== $sum['total_left_to_pay'])
    <tr>
        <td class="hidden-sm hidden-xs" colspan="2">&nbsp;</td> <!-- handle -->
        <td colspan="2" class="text-end"> <!-- title -->
            <small>{{ __('firefly.sum') }} ({{ $sum['currency_name'] }}) ({{ __('firefly.left_to_pay_active_bills') }})</small>
        </td>
        <td class="text-end"> <!-- amount -->
            {!! format_amount_by_symbol($sum['total_left_to_pay'], $sum['currency_symbol'], $sum['currency_decimal_places']) !!}
        </td>
        <td class="hidden-sm hidden-xs" colspan="3">&nbsp;</td> <!-- handle -->
    </tr>
    @endif
        @if('0' !== $sum['per_period'])
    <tr>
        <td class="hidden-sm hidden-xs" colspan="2">&nbsp;</td> <!-- handle -->
        <td colspan="2" class="text-end"> <!-- title -->
            <small>{{ __('firefly.per_period_sum_' . $sum['period']) }} ({{ $sum['currency_name'] }})
                ({{ __('firefly.active_bills_only') }})</small>
        </td>
        <td class="text-end"> <!-- amount -->
            {!!  format_amount_by_symbol($sum['per_period'], $sum['currency_symbol'], $sum['currency_decimal_places']) !!}
        </td>
        <td class="hidden-sm hidden-xs" colspan="3">&nbsp;</td> <!-- handle -->
    </tr>
    @endif
    @endforeach
    </tbody>
    @endif

</table>
@endforeach

@if(count($totals) > 0)

<h5 class="m-3">Totals</h5>

<table class="table table-bordered table-hover mb-3">
    <tbody>
    @foreach($totals as $sum)
    @if('0' !== $sum['avg'])
    <tr>
        <td class="text-end"> <!-- title -->
            <small>{{ __('firefly.sum') }} ({{ $sum['currency_name'] }}) ({{ __('firefly.active_exp_bills_only_total') }})</small>
        </td>
        <td class="text-end"> <!-- amount -->
            {!! format_amount_by_symbol($sum['avg'], $sum['currency_symbol'], $sum['currency_decimal_places']) !!}
        </td>
    </tr>
    @endif
    @if('0' !== $sum['per_period'])
    <tr>
        <td class="text-end"> <!-- title -->
            <small>{{ __('firefly.per_period_sum_' . $sum['period']) }} ({{ $sum['currency_name'] }})
                ({{ __('firefly.active_bills_only_total') }})</small>
        </td>
        <td class="text-end"> <!-- amount -->
            {!! format_amount_by_symbol($sum['per_period'], $sum['currency_symbol'], $sum['currency_decimal_places']) !!}
        </td>
    </tr>
    @endif
    @endforeach
    </tbody>

</table>
@endif
