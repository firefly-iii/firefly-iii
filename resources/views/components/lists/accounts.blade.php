<div class="ml-1">
    {{ $accounts->links('pagination.bootstrap-4') }}
</div>
<table class="table table-responsive table-hover" id="sortable-table">
    <thead>
    <tr>
        <th class="hidden-sm hidden-xs">&nbsp;</th>
        <th>{{ trans('list.name') }}</th>
        @if('asset' === $objectType)

        <th class="hidden-sm hidden-xs hidden-md">{{ trans('list.role') }}</th>
        @endif
        @if('liabilities' === $objectType)
            <th>{{ trans('list.liability_type') }}</th>
            <th>{{ trans('form.liability_direction') }}</th>
            <th>{{ trans('list.interest') }} ({{ trans('list.interest_period') }})</th>
        @endif
        <th class="hidden-sm hidden-xs">{{ trans('form.account_number') }}</th>
        @if('liabilities' !== $objectType)
        <th class="text-right">{{ trans('list.currentBalance') }}</th>
        @endif
        @if('liabilities' === $objectType)
        <th class="text-right">
            {{ trans('firefly.left_in_debt') }}
        </th>
        @endif
        <th class="hidden-sm hidden-xs">{{ trans('list.active') }}</th>
        {{-- hide last activity to make room for other stuff --}}
        @if('liabilities' !== $objectType)
        <th class="hidden-sm hidden-xs hidden-md">{{ trans('list.lastActivity') }}</th>
        @endif
        <th
            class="fifteen hidden-sm hidden-xs hidden-md">{{ trans('list.balanceDiff') }}</th>
        <th class="hidden-sm hidden-xs">&nbsp;</th>
    </tr>
    </thead>
    <tbody>
    @foreach($accounts as $account)
    <tr class="sortable-object" data-id="{{ $account->id }}" data-order="{{ $account->order }}" data-position="{{ $loop->index }}">
        <td class="hidden-sm hidden-xs">
            <span class="fa fa-fw fa-bars object-handle"></span>
        </td>
        <td>
            <a href="{{ route('accounts.show',$account->id) }}">{{ $account->name }}</a>
            {% if account.location %}
            <span class="fa fa-fw fa-map-marker"></span>
            {% endif %}
            {% if account.attachments.count() > 0 %}
            <span class="fa fa-fw fa-paperclip"></span>
            {% endif %}
        </td>
        {% if objectType == "asset" %}
        <td class="hidden-sm hidden-xs hidden-md">
            {% for entry in account.accountmeta %}
            {% if entry.name == 'account_role' %}
            {{ __('firefly.account_role_'.$entry['data']) }}
            {% endif %}
            {% endfor %}
        </td>
        {% endif %}
        {% if objectType == 'liabilities' %}
        <td>{{ $account->accountTypeString }}</td>
        <td>{{ trans('firefly.liability_direction_' . $account->liability_direction . '_short')  }}</td>
        <td>{{ $account->interest }}% ({{ strtolower($account->interestPeriod) }})</td>
        {% endif %}
        <td class="hidden-sm hidden-xs">{{ $account->iban }}{% if account.iban == '' %}{{ accountGetMetaField($account, 'account_number') }}{% endif %}</td>
        {% if objectType != 'liabilities' %}
        <td class="text-right">
                <span class="mr-2">
                    {% for key, balance in account.endBalances %}
                        <span title="{{ $key }}">
                        {% if 'balance' == key %}
                            {% if not convertToPrimary %}
                                {{ formatAmountBySymbol($balance, $account->currency->symbol, $account->currency->decimal_places)  }}
                            {% endif %}
                        {% elseif 'pc_balance' == key %}
                            {% if convertToPrimary %}
                                {{ formatAmountBySymbol($balance, $primaryCurrency->symbol, $primaryCurrency->decimal_places)  }}
                            {% endif %}
                        {% else %}
                            ({{ formatAmountByCode($balance, $key)  }})
                        {% endif %}
                        </span>
                    {% endfor %}
                </span>
        </td>
        {% endif %}
        {% if objectType == 'liabilities' %}
        <td class="text-right">
            {% if '-' != account.current_debt %}
            <span class="text-info money-transfer">
                        {{ formatAmountBySymbol($account->current_debt, $account->currency->symbol, $account->currency->decimal_places, false) }}
                    </span>
            {% endif %}
        </td>
        {% endif %}
        <td class="hidden-sm hidden-xs">
            {% if account.active %}
            <span class="fa fa-fw fa-check"></span>
            {% else %}
            <span class="fa fa-fw fa-ban"></span>
            {% endif %}
        </td>
        {# hide last activity to make room for other stuff #}
        {% if objectType != 'liabilities' %}
        {% if account.lastActivityDate %}
        <td class="hidden-sm hidden-xs hidden-md">
            <!-- {{ $account->lastActivityDate }} -->
            {{ $account->lastActivityDate->isoFormat($monthAndDayFormat) }}
        </td>
        {% else %}
        <td class="hidden-sm hidden-xs hidden-md">
            <em>{{ 'never'|_ }}</em>
        </td>
        {% endif %}
        {% endif %}
        <td class="text-right hidden-sm hidden-xs hidden-md">
                <span class="mr-1">
                    {% for key, balance in account.differences %}
                        <span title="{{ $key }}">
                              {% if 'balance' == key %}
                                  {% if not convertToPrimary %}
                                      {{ formatAmountBySymbol($balance, $account->currency->symbol, $account->currency->decimal_places)  }}
                                  {% endif %}
                              {% elseif 'pc_balance' == key %}
                                  {% if convertToPrimary %}
                                      {{ formatAmountBySymbol($balance, $primaryCurrency->symbol, $primaryCurrency->decimal_places)  }}
                                  {% endif %}
                              {% else %}
                                  ({{ formatAmountByCode($balance, $key)  }})
                              {% endif %}
                            </span>
                    {% endfor %}
                </span>
        </td>
        <td class="hidden-sm hidden-xs">
            <div class="btn-group btn-group-xs pull-right">
                <button type="button" class="btn btn-default dropdown-toggle" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                    {{ __('firefly.actions') }} <span class="caret"></span></button>
                <ul class="dropdown-menu dropdown-menu-right" role="menu">
                    <li><a href="{{ route('accounts.edit',$account->id) }}"><span class="fa fa-fw fa-pencil"></span> {{ __('firefly.edit') }}</a></li>
                    <li><a href="{{ route('accounts.delete',$account->id) }}"><span class="fa fa-fw fa-trash"></span> {{ __('firefly.delete') }}</a></li>
                    {% if objectType == 'asset' %}
                    <li><a href="{{ route('accounts.reconcile',$account->id) }}"><span class="fa fa-fw fa-check"></span> {{ __('firefly.reconcile_this_account') }}</a></li>
                    {% endif %}
                </ul>
            </div>
        </td>
    </tr>

    @endforeach
    </tbody>
</table>
<div class="pl-3">
    {{ $accounts->links('pagination.bootstrap-4')}}
</div>
