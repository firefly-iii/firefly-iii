<table class="table table-hover sortable">
    <thead>
    <tr>
        <th data-defaultsign="az">{{ __('firefly.name') }}</th>
        <th data-defaultsign="_19" class="text-end">{{ __('firefly.total') }}</th>
        <th data-defaultsign="_19" class="text-right hidden-xs">{{ __('firefly.average') }}</th>
        <th data-defaultsort="disabled"></th>
    </tr>
    </thead>
    <tbody>
    @foreach($report['accounts'] as $account)
        @if($loop->index > $listLength)
            <tr class="overListLength">
        @else
            <tr>
        @endif
        <td data-value="{{ $account['name'] }}">
            <a href="{{ route('accounts.show',$account['id']) }}">{{ $account['name'] }}</a>
            @if($account['count'] > 1)
                <br/>
                <small>
                    {{ $account['count'] }} {{ strtolower(__('transactions')) }}
                </small>
            @endif
        </td>
        <td data-value="{{ $account['sum'] }}" class="text-end">
            {!! format_amount_by_symbol($account['sum'], $account['currency_symbol'], $account['currency_decimal_places']) !!}
        </td>
        <td class="text-right hidden-xs" data-value="{{ $account['average'] }}">
            @if($account['count'] > 1)
                {!! format_amount_by_symbol($account['average'], $account['currency_symbol'], $account['currency_decimal_places']) !!}
            @else
                &mdash;
            @endif
        </td>
        <td>
            <span class="bi bi-info-circle text-muted firefly-info-button" data-location="{{ $type }}"
               data-account-id="{{ $account['id'] }}" data-currency-id="{{ $account['currency_id'] }}"></span>
        </td>
        </tr>
    @endforeach
    </tbody>

    <tfoot>
    @if(count($report['accounts']) >$listLength)
        <tr>
            <td colspan="4" class="active">
                <a href="#" class="listLengthTrigger">{{ trans('firefly.show_full_list', ['number' => $incomeTopLength]) }}</a>
            </td>
        </tr>
    @endif
    @foreach($report['sums'] as $sum)
        <tr>
            <td><em>{{ __('firefly.sum') }} ({{ $sum['currency_name'] }})</em></td>
            <td class="text-end">
                {!! format_amount_by_symbol($sum['sum'], $sum['currency_symbol'], $sum['currency_decimal_places']) !!}
            </td>
            <td>&nbsp;</td>
        </tr>
    @endforeach
</table>
