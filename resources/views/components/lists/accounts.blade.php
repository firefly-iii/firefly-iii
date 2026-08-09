<div class="m-2">
    {{ $accounts->links('pagination.bootstrap-4') }}
</div>
<table class="table table-sm table-hover" id="sortable-table">
    <thead>
    <tr>
        <th class="w-5">&nbsp;</th>
        <th class="w-20">{{ trans('list.name') }}</th>
        @if('asset' === $objectType)

        <th {{-- hide on LG and smaller. --}} class="d-lg-table-cell d-none">{{ trans('list.role') }}</th>
        @endif
        @if('liabilities' === $objectType)
            <th>{{ trans('list.liability_type') }}</th>
            <th>{{ trans('form.liability_direction') }}</th>
            <th>{{ trans('list.interest') }} ({{ trans('list.interest_period') }})</th>
        @endif
        <th>{{ trans('form.account_number') }}</th>
        @if('liabilities' !== $objectType)
        <th class="text-end">{{ trans('list.currentBalance') }}</th>
        @endif
        @if('liabilities' === $objectType)
        <th class="text-end">
            {{ trans('firefly.left_in_debt') }}
        </th>
        @endif
        <th {{-- hide on SM --}} class="d-md-table-cell d-none">{{ trans('list.active') }}</th>
        {{-- hide last activity to make room for other stuff --}}
        @if('liabilities' !== $objectType)
        <th {{-- hide on LG and smaller. --}} class="d-lg-table-cell d-none">{{ trans('list.lastActivity') }}</th>
        @endif
        <th  {{-- hide on SM --}} class="w-15 d-md-table-cell d-none text-end">{{ trans('list.balanceDiff') }}</th>
        <th>&nbsp;</th>
    </tr>
    </thead>
    <tbody>
    @foreach($accounts as $account)
    <tr class="sortable-object" data-id="{{ $account->id }}" data-order="{{ $account->order }}" data-position="{{ $loop->index }}">
        <td>
            <span class="btn btn-sm bi bi-list object-handle"></span>
        </td>
        <td>
            <a href="{{ route('accounts.show',$account->id) }}">{{ $account->name }}</a>
            @if($account->location)
                <span class="bi bi-map"></span>
            @endif
            @if($account->attachments->count() > 0)
                <span class="bi bi-paperclip"></span>
            @endif
        </td>
        @if('asset' === $objectType)
        <td {{-- hide on LG and smaller. --}} class="d-lg-table-cell d-none">
            @foreach($account->accountMeta as $entry)
            @if('account_role' === $entry->name)
                {{ __('firefly.account_role_'.$entry['data']) }}
            @endif
            @endforeach
        </td>
        @endif
        @if('liabilities' === $objectType)
        <td>{{ $account->accountTypeString }}</td>
        <td>{{ trans('firefly.liability_direction_' . $account->liability_direction . '_short')  }}</td>
        <td>{{ $account->interest }}% ({{ strtolower($account->interestPeriod) }})</td>
        @endif
        <td>{{ $account->iban }} @if('' === $account->iban) {{ account_get_meta_field($account, 'account_number') }}@endif</td>
        @if('liabilities' !== $objectType)
        <td class="text-end">
                <span class="mr-2">
                    @foreach($account->endBalances as $key => $balance)
                        <span title="{{ $key }}">
                        @if('balance' === $key)
                            @if(!$convertToPrimary)
                                {!! format_amount_by_symbol($balance, $account->currency->symbol, $account->currency->decimal_places) !!}
                            @endif
                        @elseif('pc_balance' === $key)
                            @if($convertToPrimary)
                                {!! format_amount_by_symbol($balance, $primaryCurrency->symbol, $primaryCurrency->decimal_places)   !!}
                            @endif
                        @else
                            ({!! \FireflyIII\Support\Facades\Steam::formatAmountByCode($balance, $key)   !!})
                        @endif
                        </span>
                    @endforeach
                </span>
        </td>
        @endif
        @if('liabilities' === $objectType)
        <td class="text-end">
            @if('' !== $account->current_debt)
            <span class="text-info money-transfer">
                        {!!  format_amount_by_symbol($account->current_debt, $account->currency->symbol, $account->currency->decimal_places, false)  !!}
                    </span>
            @endif
        </td>
        @endif
        <td  {{-- hide on SM --}} class="d-md-table-cell d-none">
            @if($account->active)
                <span class="bi bi-check"></span>
            @endif
                @if(!$account->active)
            <span class="bi bi-ban"></span>
            @endif
        </td>
        {{-- hide last activity to make room for other stuff --}}
        @if('liabilities' !== $objectType)
        @if($account->lastActivityDate)
        <td {{-- hide on LG and smaller. --}} class="d-lg-table-cell d-none">
            <!-- {{ $account->lastActivityDate }} -->
            {{ $account->lastActivityDate?->isoFormat($monthAndDayFormat) }}
        </td>
        @else
        <td {{-- hide on LG and smaller. --}} class="d-lg-table-cell d-none">
            <em>{{ __('firefly.never') }}</em>
        </td>
        @endif
        @endif
        <td {{-- hide on SM --}} class="text-end d-md-table-cell d-none">
                <span class="mr-1">
                    @foreach($account->differences as $key => $balance)
                        <span title="{{ $key }}">
                            @if('balance' === $key)
                                @if(!$convertToPrimary)
                                      {!! format_amount_by_symbol($balance, $account->currency->symbol, $account->currency->decimal_places) !!}
                                  @endif
                            @elseif('pc_balance' === $key)
                                @if($convertToPrimary)
                                      {!! format_amount_by_symbol($balance, $primaryCurrency->symbol, $primaryCurrency->decimal_places) !!}
                                  @endif
                              @else
                                  ({!! \FireflyIII\Support\Facades\Steam::formatAmountByCode($balance, $key) !!})
                              @endif
                            </span>
                    @endforeach
                </span>
        </td>
        <td class="justify-content-end">
            <div class="dropdown">
                <button class="btn btn-outline-secondary btn-sm dropdown-toggle" type="button" id="action_menu_{{$account->id}}" data-bs-toggle="dropdown" aria-expanded="false">
                    {{ __('firefly.actions') }}
                </button>
                <ul class="dropdown-menu" aria-labelledby="action_menu_{{$account->id}}">
                    <li><a class="dropdown-item" href="{{ route('accounts.edit',$account->id) }}?_from={{ urlencode($FF3_FROM) }}"><span class="bi bi-pencil"></span> {{ __('firefly.edit') }}</a></li>
                    <li><a class="dropdown-item" href="{{ route('accounts.delete',$account->id) }}?_from={{ urlencode($FF3_FROM) }}"><span class="bi bi-trash"></span> {{ __('firefly.delete') }}</a></li>
                    @if('asset' === $objectType)
                        <li><a class="dropdown-item" href="{{ route('accounts.reconcile',$account->id) }}"><span class="bi bi-check"></span> {{ __('firefly.reconcile_this_account') }}</a></li>
                    @endif
                </ul>
            </div>
        </td>
    </tr>

    @endforeach
    </tbody>
</table>
<div class="m-2">
    {{ $accounts->links('pagination.bootstrap-4')}}
</div>
