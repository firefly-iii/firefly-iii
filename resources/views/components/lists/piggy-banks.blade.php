@foreach($piggyBanks as $objectGroupOrder => $objectGroup)
    <h5 class="m-3">{{ $objectGroup['object_group_title'] }}</h5>
<table class="table table-hover table-sm table-condensed" id="piggy-sortable">
    <thead>
    <tr>
        <th style="width:50px;" class="d-none d-md-table-cell">&nbsp;</th>
        <th class="d-table-cell d-md-none"></th>
        <th style="width:50px;"></th>
        <th style="width:30%;">{{ __('firefly.piggy_bank') }}</th>
        <th style="width:10%;" class="text-end">{{ __('firefly.saved_so_far') }}</th>
        <th class="d-none d-md-table-cell"></th>
        <th class="d-none d-md-table-cell"></th>
        <th class="d-none d-md-table-cell"></th>
        <th style="width:10%;" class="text-end">{{ __('firefly.target_amount') }}</th>
        <th style="width:10%;" class="d-none d-md-table-cell text-end">{{ __('firefly.left_to_save') }}</th>
        <th style="width:15%;" class="d-none d-md-table-cell text-end">{{ __('firefly.suggested_savings_per_month') }}</th>
    </tr>
    </thead>
    @if(count($objectGroup['piggy_banks']) > 0)
    <tbody class="piggy-connected-list" @if(0 !== $objectGroupOrder)data-title="{{ $objectGroup['object_group_title'] }}"@else data-title=""@endif>
    @foreach($objectGroup['piggy_banks'] as $piggy)
    <tr class="piggy-sortable" data-id="{{ $piggy['id'] }}" data-name="{{ $piggy['name'] }}" data-order="{{ $piggy['order'] }}">
        <td class="d-none d-md-table-cell">
            <span class="bi bi-list piggy-handle"></span>
            <span class="loadSpin"></span>
        </td>
        <td class="d-table-cell d-md-none">
            <div class="btn-group btn-group-sm">
                <a href="{{ route('piggy-banks.remove-money-mobile', $piggy['id']) }}" class="btn btn-secondary btn-sm"><span class="bi bi-plus"></span></a>
                <a href="{{ route('piggy-banks.add-money-mobile', $piggy['id']) }}" class="btn btn-secondary btn-sm"><span class="bi bi-dash"></span></a>
            </div>
        </td>
        <td style="width:100px;">
            <div class="btn-group btn-group-sm">
                <a href="{{ route('piggy-banks.edit', $piggy['id']) }}" class="btn btn-secondary btn-sm"><span class="bi bi-pencil"></span></a>
                <a href="{{ route('piggy-banks.delete', $piggy['id']) }}" class="btn btn-danger btn-sm"><span class="bi bi-trash"></span></a>
            </div>
        </td>
        <td>
            <a href="{{ route('piggy-banks.show', $piggy['id']) }}" title="{{ $piggy['name'] }}">{{ $piggy['name'] }}</a>
            @if(count($piggy['attachments']) > 0)
                <span class="fa fa-fw fa-paperclip"></span>
            @endif
        </td>
        <td class="text-end piggySaved">
                    <span title="Saved so far" class="text-end">
                        {!! format_amount_by_symbol($piggy['current_amount'],$piggy['currency_symbol'],$piggy['currency_decimal_places']) !!}
                        @if($convertToPrimary and $piggy['currency_id'] !== $primaryCurrency->id && null !== $piggy['pc_current_amount'])
                            ({!! format_amount_by_symbol($piggy['pc_current_amount'],$primaryCurrency->symbol,$primaryCurrency->decimal_places) !!})
                        @endif
                    </span>
        </td>
        <td class="d-none d-md-table-cell" style="width:40px;">
            @if($piggy['current_amount'] > 0)
            <a href="{{ route('piggy-banks.remove-money', $piggy['id']) }}" class="btn btn-secondary btn-sm removeMoney" data-id="{{ $piggy['id'] }}">
                <span data-id="{{ $piggy['id'] }}" class="bi bi-dash"></span></a>
            @endif
        </td>

        <td class="piggyBar d-none d-md-table-cell">
            @if(null !== $piggy['percentage'])
            <div class="progress progress mb-0">
                <div
                    @if(100 === $piggy['percentage'])
                        class="progress-bar progress-bar-success w-{{ round(max(30, $piggy['percentage'])) }}"
                    @elseif(0 === $piggy['percentage'])
                        class="progress-bar progress-bar-warning w-{{ round(max(30, $piggy['percentage'])) }}"
                    @else
                        class="progress-bar progress-bar-info w-{{ round(max(30, $piggy['percentage'])) }}"
                    @endif
                    role="progressbar" aria-valuenow="{{ $piggy['percentage'] }}" aria-valuemin="0" aria-valuemax="100">
                    {{ $piggy['percentage'] }}%
                </div>
            </div>
            @endif
        </td>


        <td style="width:40px;" class="d-none d-md-table-cell">
            @if($piggy['left_to_save'] > 0 || null === $piggy['left_to_save'])
            <a href="{{ route('piggy-banks.add-money', $piggy['id']) }}" class="btn btn-secondary btn-sm addMoney" data-id="{{ $piggy['id'] }}">
                <span data-id="{{ $piggy['id'] }}" class="b bi-plus"></span></a>
            @endif
        </td>
        <td class="text-end">
            @if(null !== $piggy['target_amount'] && 0 !== $piggy['target_amount'])
            <span title="{{ __('firefly.target_amount') }}">{!! format_amount_by_symbol($piggy['target_amount'],$piggy['currency_symbol'],$piggy['currency_decimal_places']) !!}</span>
            @if($convertToPrimary && $piggy['currency_id'] !== $primaryCurrency->id && null !== $piggy['pc_target_amount'])
            (<span title="{{ __('firefly.target_amount') }}">{!! format_amount_by_symbol($piggy['pc_target_amount'],$primaryCurrency->symbol, $primaryCurrency->decimal_places)  !!}</span>)
            @endif
            @endif
        </td>
        <td class="text-end d-none d-md-table-cell">
            @if($piggy['left_to_save'] > 0)
            <span title="{{ __('firefly.left_to_save') }}">{!! format_amount_by_symbol($piggy['left_to_save'],$piggy['currency_symbol'],$piggy['currency_decimal_places']) !!}</span>
                @if($convertToPrimary && $piggy['currency_id'] !== $primaryCurrency->id && null !== $piggy['pc_left_to_save'])
            (<span title="{{ __('firefly.left_to_save') }}">{!! format_amount_by_symbol($piggy['pc_left_to_save'], $primaryCurrency->symbol,$primaryCurrency->decimal_places) !!}</span>)
            @endif
            @endif
        </td>
        <td class="d-none d-md-table-cell text-end">
            @if(null !== $piggy['target_date'] && null !== $piggy['save_per_month'])
            {!! format_amount_by_symbol($piggy['save_per_month'], $piggy['currency_symbol'], $piggy['currency_decimal_places']) !!}
                @if($convertToPrimary && $piggy['currency_id'] !== $primaryCurrency->id && null !== $piggy['pc_save_per_month'])
            ({!! format_amount_by_symbol($piggy['pc_save_per_month'], $primaryCurrency->symbol, $primaryCurrency->decimal_places) !!})
            @endif
            @endif
        </td>
    </tr>
    @endforeach
    <tr>
        <td class="d-none d-md-table-cell">&nbsp;</td> {{-- handle --}}
        <td class="d-table-cell d-md-none">&nbsp;</td> {{-- mobile buttons --}}
        <td>&nbsp;</td> {{-- normal buttons --}}
        <td>&nbsp;</td> {{-- title --}}
        <td class="text-end">
            @foreach($objectGroup['sums'] as $sum)
            {!! format_amount_by_symbol($sum['saved'], $sum['currency_symbol'], $sum['currency_decimal_places']) !!}<br />
            @endforeach
        </td>
        <td class="d-none d-md-table-cell">&nbsp;</td> {{-- remove money --}}
        <td class="d-none d-md-table-cell">&nbsp;</td> {{-- progress --}}
        <td class="d-none d-md-table-cell">&nbsp;</td> {{-- add money --}}
        <td class="text-end">
            @foreach($objectGroup['sums'] as $sum)
            {!! format_amount_by_symbol($sum['target'], $sum['currency_symbol'], $sum['currency_decimal_places']) !!}<br />
            @endforeach
        </td>
        <td class="text-end d-none d-md-table-cell">
            @foreach($objectGroup['sums'] as $sum)
            {!! format_amount_by_symbol($sum['left_to_save'], $sum['currency_symbol'], $sum['currency_decimal_places']) !!}<br />
            @endforeach
        </td>
        <td class="d-none d-md-table-cell text-end">
            @foreach($objectGroup['sums'] as $sum)
            {!! format_amount_by_symbol($sum['save_per_month'], $sum['currency_symbol'], $sum['currency_decimal_places']) !!}<br />
            @endforeach
        </td>
    </tr>
    </tbody>
    @endif
</table>
@endforeach
