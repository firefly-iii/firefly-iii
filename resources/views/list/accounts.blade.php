<div class="ml-1">
    {{ accounts.links('pagination.bootstrap-4')|raw }}
</div>
<table class="table table-responsive table-hover" id="sortable-table">
    <thead>
    <tr>
        <th class="hidden-sm hidden-xs">&nbsp;</th>
        <th>{{ trans('list.name') }}</th>
        {% if objectType == 'asset' %}
            <th class="hidden-sm hidden-xs hidden-md">{{ trans('list.role') }}</th>
        @endif
        {% if objectType == 'liabilities' %}
            <th>{{ trans('list.liability_type') }}</th>
            <th>{{ trans('form.liability_direction') }}</th>
            <th>{{ trans('list.interest') }} ({{ trans('list.interest_period') }})</th>
        @endif
        <th class="hidden-sm hidden-xs">{{ trans('form.account_number') }}</th>
        {% if objectType != 'liabilities' %}
            <th class="text-end">{{ trans('list.currentBalance') }}</th>
        @endif
        {% if objectType == 'liabilities' %}
            <th class="text-end">
                {{ trans('firefly.left_in_debt') }}
            </th>
        @endif
        <th class="hidden-sm hidden-xs">{{ trans('list.active') }}</th>
        {{-- hide last activity to make room for other stuff --}}
        {% if objectType != 'liabilities' %}
            <th class="hidden-sm hidden-xs hidden-md">{{ trans('list.lastActivity') }}</th>
        @endif
        <th
            class="fifteen hidden-sm hidden-xs hidden-md">{{ trans('list.balanceDiff') }}</th>
        <th class="hidden-sm hidden-xs">&nbsp;</th>
    </tr>
    </thead>
    <tbody>
    {% for account in accounts %}
        <tr class="sortable-object" data-id="{{ account.id }}" data-order="{{ account.order }}" data-position="{{ loop.index0 }}">
            <td class="hidden-sm hidden-xs">
                <span class="fa fa-bars object-handle"></span>
            </td>
            <td>
                <a href="{{ route('accounts.show',account.id) }}">{{ account.name }}</a>
                {% if account.location %}
                    <span class="fa fa-map-marker"></span>
                @endif
                {% if account.attachments.count() > 0 %}
                    <span class="bi bi-paperclip"></span>
                @endif
            </td>
            {% if objectType == "asset" %}
                <td class="hidden-sm hidden-xs hidden-md">
                    {% for entry in account.accountmeta %}
                        {% if entry.name == 'account_role' %}
                            {{ ('account_role_'~entry.data)|_ }}
                        @endif
                    @endforeach
                </td>
            @endif
            {% if objectType == 'liabilities' %}
            <td>{{ account.accountTypeString }}</td>
            <td>{{ trans('firefly.liability_direction_'~account.liability_direction~'_short')  }}</td>
            <td>{{ account.interest }}% ({{ account.interestPeriod|lower }})</td>
            @endif
            <td class="hidden-sm hidden-xs">{{ $account['iban'] }}{% if $account['iban'] == '' %}{{ accountGetMetaField(account, 'account_number') }}@endif</td>
            {% if objectType != 'liabilities' %}
            <td class="text-end">
                <span class="mr-2">
                    {% for key, balance in account.endBalances %}
                        <span title="{{ key }}">
                        {% if 'balance' == key %}
                            {% if not convertToPrimary %}
                                {!! format_amount_by_symbol(balance, account.currency.symbol, account.currency.decimal_places)  }}
                            @endif
                        {% elseif 'pc_balance' == key %}
                            {% if convertToPrimary %}
                                {!! format_amount_by_symbol(balance, $primaryCurrency->symbol, $primaryCurrency->decimal_places)  }}
                            @endif
                        @else
                            ({{ formatAmountByCode(balance, key)  }})
                        @endif
                        </span>
                    @endforeach
                </span>
            </td>
            @endif
            {% if objectType == 'liabilities' %}
            <td class="text-end">
                {% if '-' != account.current_debt %}
                    <span class="text-info money-transfer">
                        {!! format_amount_by_symbol(account.current_debt, account.currency.symbol, account.currency.decimal_places, false) }}
                    </span>
                @endif
            </td>
            @endif
            <td class="hidden-sm hidden-xs">
                {% if account.active %}
                    <span class="bi bi-check"></span>
                @else
                    <span class="fa fa-ban"></span>
                @endif
            </td>
            {{-- hide last activity to make room for other stuff --}}
            {% if objectType != 'liabilities' %}
                {% if account.lastActivityDate %}
                    <td class="hidden-sm hidden-xs hidden-md">
                        <!-- {{ account.lastActivityDate }} -->
                        {{ account.lastActivityDate.isoFormat($monthAndDayFormat) }}
                    </td>
                @else
                    <td class="hidden-sm hidden-xs hidden-md">
                        <em>{{ 'never'|_ }}</em>
                    </td>
                @endif
            @endif
            <td class="text-end hidden-sm hidden-xs hidden-md">
                <span class="mr-1">
                    {% for key, balance in account.differences %}
                        <span title="{{ key }}">
                              {% if 'balance' == key %}
                                  {% if not convertToPrimary %}
                                      {!! format_amount_by_symbol(balance, account.currency.symbol, account.currency.decimal_places)  }}
                                  @endif
                              {% elseif 'pc_balance' == key %}
                                  {% if convertToPrimary %}
                                      {!! format_amount_by_symbol(balance, $primaryCurrency->symbol, $primaryCurrency->decimal_places)  }}
                                  @endif
                              @else
                                  ({{ formatAmountByCode(balance, key)  }})
                              @endif
                            </span>
                    @endforeach
                </span>
            </td>
            <td class="hidden-sm hidden-xs">
                <div class="btn-group btn-group-sm text-end">
                    <button type="button" class="btn btn-outline-secondary dropdown-toggle" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                        {{ __('firefly.actions') }} <span class="caret"></span></button>
                    <ul class="dropdown-menu dropdown-menu-right" role="menu">
                        <li><a href="{{ route('accounts.edit',account.id) }}"><span class="bi bi-pencil"></span> {{ __('firefly.edit') }}</a></li>
                        <li><a href="{{ route('accounts.delete',account.id) }}"><span class="bi bi-trash"></span> {{ __('firefly.delete') }}</a></li>
                        {% if objectType == 'asset' %}
                        <li><a href="{{ route('accounts.reconcile',account.id) }}"><span class="bi bi-check"></span> {{ 'reconcile_this_account'|_ }}</a></li>
                        @endif
                    </ul>
                </div>
            </td>
        </tr>

    @endforeach
    </tbody>
</table>
<div class="pl-3">
    {{ accounts.links('pagination.bootstrap-4')|raw }}
</div>
