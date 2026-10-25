@extends('layout.v3.session')
@section('content')
    <div class="row" x-data="index">
        <template x-if="true === loadingPage">
            <div class="col-lg-12 text-center">
                <div id="status-box" class="p-3 install-box-border">
                    <div class="spinner-border spinner-border-sm" role="status">
                        <span class="visually-hidden">{{ __('firefly.thinking') }}</span>
                    </div>
                </div>
            </div>
        </template>
        <template x-for="(data, tableId) in groupedObjects" :key="tableId">
            <div class="col-lg-12 col-md-12 col-sm-12 data-holder mb-2">
                <div class="card" :id="'account-index-{{ $objectType }}-' + tableId">
                    <div class="card-header">
                        <div class="row">
                            <div class="col">
                                <template x-if="'ungrouped' === data.title">
                                    <h3 class="card-title" x-text="i18next.t('firefly.default_group_title_name')"></h3>
                                </template>
                                <template x-if="'ungrouped' !== data.title">
                                    <h3 class="card-title" x-text="data.title"></h3>
                                </template>
                            </div>
                                <div class="col text-end" role="group">
                                    <a href="{{ route('accounts.create', [$objectType]) . '?_from=' . urlencode($FF3_FROM) }}" class="btn btn-sm btn-outline-success">
                                        <span class="bi bi-plus-circle"></span> {{ __('firefly.make_new_'. $objectType . '_account') }}
                                    </a>
                                </div>
                        </div>
                    </div>
                    <div class="card-body p-0">
                        <table :data-sort-identifier="tableId" class="table table-valign-middle table-sm table-hover sortable">
                            <thead>
                            <template x-if="sortableTable.totalPages[tableId] > 1">
                                <tr>
                                    <th colspan="10">
                                        <x-elements.alpine.page-navigation />
                                    </th>
                                </tr>
                            </template>
                            <tr>
                                <th data-sort-column="order" class="w-5">
                                    <span class="title">&nbsp;</span>
                                </th>
                                <th data-sort-column="name" data-filter-column="name" class="w-20"><span class="title">{{ trans('list.name') }}</span></th>
                                <template x-if="'asset' === objectType">
                                    {{-- hide on LG and smaller. --}}
                                    <th data-sort-column="role" class="w-10 d-lg-table-cell d-none"><span class="title">{{ trans('list.role') }}</span></th>
                                </template>
                                <template x-if="'liabilities' === objectType">
                                    <th class="w-10" data-sort-column="account_type_id"><span class="title">{{ trans('list.liability_type') }}</span></th>
                                </template>
                                <template x-if="'liabilities' === objectType">
                                    <th  class="w-10" data-sort-column="liability_direction"><span class="title">{{ trans('form.liability_direction') }}</span></th>
                                </template>
                                <template x-if="'liabilities' === objectType">
                                    <th data-sort-column="liability_interest"><span class="title">{{ trans('list.interest') }}</span>
                                        ({{ trans('list.interest_period') }})
                                    </th>
                                </template>
                                <th  class="w-15" data-sort-column="account_number_and_iban" data-filter-column="account_number_and_iban"><span class="title">{{ trans('form.account_number') }}</span></th>
                                <template x-if="'liabilities' !== objectType">
                                    <th  class="w-10" data-sort-column="current_balance"><span class="title">{{ trans('list.currentBalance') }}</span></th>
                                </template>
                                <template x-if="'liabilities' === objectType">
                                    <th data-sort-column="debt_amount">
                                        <span class="title">{{ trans('firefly.left_in_debt') }}</span>
                                    </th>
                                </template>
                                <th  class="w-5" {{-- hide on SM --}} class="d-md-table-cell d-none">{{ trans('list.active') }}</th>
                                {{-- hide last activity to make room for other stuff --}}
                                <template x-if="'liabilities' !== objectType">
                                    {{-- hide on LG and smaller. --}}
                                    <th data-sort-column="last_activity" class="w-10 d-lg-table-cell d-none"><span class="title">{{ trans('list.lastActivity') }}</span></th>
                                </template>
                                {{-- hide on SM --}}
                                <th data-sort-column="balance_difference" class="w-10 text-end d-lg-table-cell d-none"><span class="title">{{ trans('list.balanceDiff') }}</span></th>
                                <th  class="w-5">&nbsp;</th>
                            </tr>
                            <tr x-show="true === sortableTable.loadingNewSorts[tableId]">
                                <th colspan="12">
                                    <div class="p-3 text-center">
                                        <div class="spinner-border spinner-border-sm" role="status">
                                            <span class="visually-hidden">{{ __('firefly.thinking') }}</span>
                                        </div>
                                    </div>
                                </th>
                            </tr>
                            </thead>
                            <tbody>
                            <template x-for="account in data.items" :key="account.id">
                                <tr :data-id="account.id">
                                    <td>
                                        <template
                                            x-if="('asset' === objectType || 'liabilities' === objectType) && 'asc' === sortableTable.sortDirections[tableId] && 'order' === sortableTable.sortColumns[tableId] && groupedObjects[tableId].items.length > 1">
                                            <span class="btn btn-outline-secondary btn-sm bi bi-list object-handle"></span>
                                        </template>
                                    </td>
                                    <td>
                                        <a :href="'./accounts/show/' + account.id" :title="account.name"
                                           x-text="account.name"></a>
                                        <template x-if="account.location">
                                            <span class="bi bi-map"></span>
                                        </template>
                                        <template x-if="true === account.has_attachments">
                                            <span class="bi bi-paperclip"></span>
                                        </template>
                                    </td>
                                    <template x-if="'asset' === objectType">
                                        <td class="d-lg-table-cell d-none">
                                            <template x-if="null !== account.role">
                                                <span x-text="i18next.t('firefly.account_role_' + account.role)"></span>
                                            </template>
                                            <template x-if="null === account.role || '' === account.role">
                                                <span>~</span>
                                            </template>
                                        </td>
                                    </template>
                                    <template x-if="'liabilities' === objectType">
                                        <td>
                                            <span x-text="account.liability_type"></span>
                                        </td>
                                    </template>
                                    <template x-if="'liabilities' === objectType">
                                        <td>
                                            <span x-text="account.liability_direction"></span>
                                        </td>
                                    </template>
                                    <template x-if="'liabilities' === objectType">
                                        <td>
                                            <span x-text="account.liability_interest"></span>%
                                            (<span x-text="account.liability_interest_period"></span>)
                                        </td>
                                    </template>
                                    <td>
                                        <span x-text="account.iban"></span>
                                        <template x-if="'' === account.iban">
                                            <span x-text="account.account_number"></span>
                                        </template>
                                    </td>
                                    <template x-if="'liabilities' !== objectType">
                                        <td class="text-end">
                                            <template x-if="!convertToPrimary || account.primary_currency_id === account.currency_id">
                                            <span>
                                                <template x-if="0.0 === account.current_balance_float">
                                                    <span class="money-neutral" x-text="account.current_balance"></span>
                                                </template>
                                                <template x-if="account.current_balance_float > 0.0">
                                                    <span class="money-positive" x-text="account.current_balance"></span>
                                                </template>
                                                <template x-if="account.current_balance_float < 0.0">
                                                    <span class="money-negative" x-text="account.current_balance"></span>
                                                </template>
                                            </span>
                                            </template>
                                            <template x-if="convertToPrimary && account.primary_currency_id !== account.currency_id">
                                            <span>
                                                <template x-if="0.0 === account.current_balance_float">
                                                    <span>~ <span class="money-neutral" x-text="account.pc_current_balance"></span></span>
                                                </template>
                                                <template x-if="account.current_balance_float > 0.0">
                                                    <span>~ <span class="money-positive" x-text="account.pc_current_balance"></span></span>
                                                </template>
                                                <template x-if="account.current_balance_float < 0.0">
                                                    <span>~ <span class="money-negative" x-text="account.pc_current_balance"></span></span>
                                                </template>
                                                   <template x-if="0.0 === account.current_balance_float">
                                                        <span>(<span class="money-neutral" x-text="account.current_balance"></span>)</span>
                                                    </template>
                                                    <template x-if="account.current_balance_float > 0.0">
                                                        <span>(<span class="money-positive" x-text="account.current_balance"></span>)</span>
                                                    </template>
                                                    <template x-if="account.current_balance_float < 0.0">
                                                        <span>(<span class="money-negative" x-text="account.current_balance"></span>)</span>
                                                    </template>
                                            </span>
                                            </template>
                                        </td>
                                    </template>
                                    <template x-if="'liabilities' === objectType">
                                        <td class="text-end">
                                            <template x-if="!convertToPrimary || account.primary_currency_id === account.currency_id">
                                            <span>
                                                <template x-if="0.0 === account.current_debt_float">
                                                    <span class="money-neutral" x-text="account.current_debt"></span>
                                                </template>
                                                <template x-if="account.current_debt_float > 0.0">
                                                    <span class="money-positive" x-text="account.current_debt"></span>
                                                </template>
                                                <template x-if="account.current_debt_float < 0.0">
                                                    <span class="money-negative" x-text="account.current_debt"></span>
                                                </template>
                                            </span>
                                            </template>
                                            <template x-if="convertToPrimary && account.primary_currency_id !== account.currency_id">
                                            <span>
                                                <template x-if="0.0 === account.current_debt_float">
                                                    <span>~ <span class="money-neutral" x-text="account.pc_current_debt"></span></span>
                                                </template>
                                                <template x-if="account.current_debt_float > 0.0">
                                                    <span>~ <span class="money-positive" x-text="account.pc_current_debt"></span></span>
                                                </template>
                                                <template x-if="account.current_debt_float < 0.0">
                                                    <span>~ <span class="money-negative" x-text="account.pc_current_debt"></span></span>
                                                </template>
                                                   <template x-if="0.0 === account.current_debt_float">
                                                        <span>(<span class="money-neutral" x-text="account.current_debt"></span>)</span>
                                                    </template>
                                                    <template x-if="account.current_debt_float > 0.0">
                                                        <span>(<span class="money-positive" x-text="account.current_debt"></span>)</span>
                                                    </template>
                                                    <template x-if="account.current_debt_float < 0.0">
                                                        <span>(<span class="money-negative" x-text="account.current_debt"></span>)</span>
                                                    </template>
                                            </span>
                                            </template>
                                        </td>
                                    </template>
                                    <td class="d-md-table-cell d-none">
                                        <template x-if="true === account.active">
                                            <span class="bi bi-check text-success"></span>
                                        </template>
                                        <template x-if="false === account.active">
                                            <span class="bi bi-x text-danger"></span>
                                        </template>
                                    </td>
                                    <template x-if="'liabilities' !== objectType">
                                        <td>
                                            <template x-if="true === account.no_last_activity">
                                                <em class="text-muted" x-text="account.last_activity"></em>
                                            </template>
                                            <template x-if="false === account.no_last_activity">
                                                <span x-text="account.last_activity"></span>
                                            </template>
                                        </td>
                                    </template>
                                    <td class="text-end">
                                        <template x-if="!convertToPrimary || account.primary_currency_id === account.currency_id">
                                            <span>
                                                <template x-if="0.0 === account.balance_difference_float">
                                                    <span class="money-neutral" x-text="account.balance_difference"></span>
                                                </template>
                                                <template x-if="account.balance_difference_float > 0.0">
                                                    <span class="money-positive" x-text="account.balance_difference"></span>
                                                </template>
                                                <template x-if="account.balance_difference_float < 0.0">
                                                    <span class="money-negative" x-text="account.balance_difference"></span>
                                                </template>
                                            </span>
                                        </template>
                                        <template x-if="convertToPrimary && account.primary_currency_id !== account.currency_id">
                                            <span>
                                                <template x-if="0.0 === account.balance_difference_float">
                                                    <span>~ <span class="money-neutral" x-text="account.pc_balance_difference"></span></span>
                                                </template>
                                                <template x-if="account.balance_difference_float > 0.0">
                                                    <span>~ <span class="money-positive" x-text="account.pc_balance_difference"></span></span>
                                                </template>
                                                <template x-if="account.balance_difference_float < 0.0">
                                                    <span>~ <span class="money-negative" x-text="account.pc_balance_difference"></span></span>
                                                </template>
                                                   <template x-if="0.0 === account.balance_difference_float">
                                                        <span>(<span class="money-neutral" x-text="account.balance_difference"></span>)</span>
                                                    </template>
                                                    <template x-if="account.balance_difference_float > 0.0">
                                                        <span>(<span class="money-positive" x-text="account.balance_difference"></span>)</span>
                                                    </template>
                                                    <template x-if="account.balance_difference_float < 0.0">
                                                        <span>(<span class="money-negative" x-text="account.balance_difference"></span>)</span>
                                                    </template>
                                            </span>
                                        </template>
                                    </td>
                                    <td class="justify-content-end">
                                        <div class="dropdown">
                                            <button class="btn btn-outline-secondary btn-sm dropdown-toggle" type="button"
                                                    :id="'action_menu_' + account.id" data-bs-toggle="dropdown"
                                                    aria-expanded="false">
                                                {{ __('firefly.actions') }}
                                            </button>
                                            <ul class="dropdown-menu" :aria-labelledby="'action_menu_' + account.id">
                                                <li><a class="dropdown-item"
                                                       :href="'./accounts/edit/' + account.id + '?_from={{ urlencode($FF3_FROM) }}'"><span
                                                            class="bi bi-pencil"></span> {{ __('firefly.edit') }}</a></li>
                                                <li><a class="dropdown-item"
                                                       :href="'./accounts/delete/' + account.id + '?_from={{ urlencode($FF3_FROM) }}'"><span
                                                            class="bi bi-trash"></span> {{ __('firefly.delete') }}</a></li>
                                                <template x-if="'asset' === objectType">
                                                    <li><a class="dropdown-item"
                                                           :href="'./accounts/reconcile/' + account.id + '/index'"><span
                                                                class="bi bi-check"></span> {{ __('firefly.reconcile_this_account') }}
                                                        </a></li>
                                                </template>
                                            </ul>
                                        </div>
                                    </td>
                                </tr>
                            </template>
                            </tbody>
                            <tfoot>
                            <template
                                x-if="data.items.length > 0 && ('asset' === objectType || 'liabilities' === objectType)">
                                <tr>
                                    <td colspan="3">&nbsp;</td>
                                    <template x-if="'asset' === objectType">
                                        <td>&nbsp;</td>
                                    </template>
                                    <template x-if="'liabilities' === objectType">
                                        <td colspan="3">&nbsp;</td>
                                    </template>
                                    <template x-if="'liabilities' !== objectType">
                                        <td class="text-end">
                                            <template x-for="(sum, code) in sums">
                                        <span>
                                            <template x-if="sum > 0">
                                                <span>
                                                <span class="money-positive" x-text="formatMoney(sum, code)"></span>
                                                    <br>
                                                </span>
                                            </template>
                                            <template x-if="sum < 0">
                                                <span>
                                                <span class="money-negative" x-text="formatMoney(sum, code)"></span>
                                                    <br>
                                                </span>
                                            </template>
                                        </span>
                                            </template>
                                        </td>
                                    </template>
                                    <template x-if="'liabilities' === objectType">
                                        <td class="text-end">
                                            <template x-for="(sum, code) in debts">
                                        <span>
                                            <template x-if="sum > 0">
                                                <span>
                                                <span class="money-positive" x-text="formatMoney(sum, code)"></span>
                                                    <br>
                                                </span>
                                            </template>
                                            <template x-if="sum < 0">
                                                <span>
                                                <span class="money-negative" x-text="formatMoney(sum, code)"></span>
                                                    <br>
                                                </span>
                                            </template>
                                        </span>
                                            </template>
                                        </td>
                                    </template>
                                    <td>&nbsp;</td> <!-- is active column -->
                                    <template x-if="'liabilities' !== objectType">
                                        <td>&nbsp;</td> {{-- last activity column --}}
                                    </template>
                                    <td class="text-end">
                                        <template x-for="(sum, code) in differences">
                                        <span>
                                            <template x-if="sum > 0">
                                                <span>
                                                <span class="money-positive" x-text="formatMoney(sum, code)"></span>
                                                    <br>
                                                </span>
                                            </template>
                                            <template x-if="sum < 0">
                                                <span>
                                                <span class="money-negative" x-text="formatMoney(sum, code)"></span>
                                                    <br>
                                                </span>
                                            </template>
                                        </span>
                                        </template>
                                    </td>
                                    <template x-if="'liabilities' === objectType">
                                        <td>&nbsp;</td>
                                    </template>

                                    <template x-if="'liabilities' !== objectType">
                                        <td>&nbsp;</td>
                                    </template>
                                </tr>
                            </template>
                            <template x-if="sortableTable.totalPages[tableId] > 1">
                                <tr>
                                    <th colspan="10">
                                        <x-elements.alpine.page-navigation />
                                    </th>
                                </tr>
                            </template>
                            </tfoot>
                        </table>
                    </div>
                    <x-elements.card-footer-with-menu
                        :route="route('accounts.create', $objectType) . '?_from=' . urlencode($FF3_FROM)"
                        :linkTitle="__('firefly.make_new_'. $objectType . '_account')"/>
                </div>
            </div>
        </template>

        <template x-if="0 === groupedObjects.length && true === active && false === loadingPage && false === sortableTable.isFiltering">
            <x-empty-page :route="route('accounts.create', [$objectType]) . '?_from=' . urlencode($FF3_FROM)"
                          type="accounts" :object-type="$objectType"/>
        </template>
        <template x-if="0 === groupedObjects.length && true === active">
            <p class="text-center"><small>
                    <em>
                        <a href="{{ route('accounts.inactive.index', $objectType) }}">
                        <span
                            x-text="i18next.t('firefly.may_inactive_accounts_link')"></span>
                        </a>
                    </em>
                </small>
            </p>
        </template>
        <template x-if="0 !== groupedObjects.length && true === active">
            <p class="text-center"><small>
                    <em>
                        <a href="{{ route('accounts.inactive.index', $objectType) }}">
                            <span x-text="i18next.t('firefly.inactive_account_link_js')"></span>
                        </a>
                    </em>
                </small>
            </p>
        </template>
        <template x-if="0 !== groupedObjects.length && false === active">
            <p class="text-center"><small>
                    <em>
                        <a href="{{ route('accounts.index', $objectType) }}"><span
                                x-text="i18next.t('firefly.active_account_link')"></span></a>
                    </em>
                </small>
            </p>
        </template>
        <template x-if="0 === groupedObjects.length && false === active">
            <p class="text-center"><small>
                    <em><a href="{{ route('accounts.index', $objectType) }}">
                        <span
                            x-text="i18next.t('firefly.no_inactive_accounts', {url: ''})"></span>
                        </a>
                    </em>
                </small>
            </p>
        </template>
    </div>
@endsection
@section('scripts')
    @vite(['js/pages/accounts/index.js'])
@endsection
