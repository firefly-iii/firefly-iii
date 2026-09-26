@extends('layout.v3.session')
@section('content')
        <div class="row" x-data="index">
            <template x-if="true === loading">
            <div class="col-lg-12 text-center">
                <div id="status-box" class="p-3 install-box-border">
                    <div class="spinner-border spinner-border-sm" role="status">
                        <span class="visually-hidden">{{ __('firefly.thinking') }}</span>
                    </div>
                </div>
            </div>
            </template>

            <div class="col-lg-12 col-md-12 col-sm-12 data-holder">
                <div class="card" id="account-index-{{ $objectType }}">
                    <x-elements.card-header-with-menu :cardTitle="trans('firefly.'.$objectType.'_accounts')" :route="route('accounts.create', $objectType) . '?_from=' . urlencode($FF3_FROM)" :linkTitle="__('firefly.make_new_'. $objectType . '_account')"/>
                    <div class="card-body p-0">
                        <x-elements.alpine.page-navigation />
                        <table class="table table-valign-middle table-sm table-hover sortable">
                            <thead>
                            <tr>
                                <th data-column="order" :class="{'w-5' : true,'sortable': true, 'sortable_sorted': 'order' === sortColumn, 'sortable_sorted_asc': 'asc' === sortDirection, 'sortable_sorted_desc': 'desc' === sortDirection }">&nbsp;</th>
                                <th data-column="name"  :class="{'w-20' : true,'sortable': true, 'sortable_sorted': 'name' === sortColumn, 'sortable_sorted_asc': 'asc' === sortDirection, 'sortable_sorted_desc': 'desc' === sortDirection }">{{ trans('list.name') }}</th>
                                <template x-if="'asset' === objectType">
                                    {{-- hide on LG and smaller. --}}
                                    <th data-column="role" :class="{'d-lg-table-cell': true, 'd-none': true,'sortable': true, 'sortable_sorted': 'role' === sortColumn, 'sortable_sorted_asc': 'asc' === sortDirection, 'sortable_sorted_desc': 'desc' === sortDirection }">{{ trans('list.role') }}</th>
                                </template>
                                <template x-if="'liabilities' === objectType">
                                    <th data-column="account_type_id" :class="{'sortable': true, 'sortable_sorted': 'account_type_id' === sortColumn, 'sortable_sorted_asc': 'asc' === sortDirection, 'sortable_sorted_desc': 'desc' === sortDirection }">{{ trans('list.liability_type') }}</th>
                                </template>
                                <template x-if="'liabilities' === objectType">
                                    <th data-column="liability_direction" :class="{'sortable': true, 'sortable_sorted': 'liability_direction' === sortColumn, 'sortable_sorted_asc': 'asc' === sortDirection, 'sortable_sorted_desc': 'desc' === sortDirection }">{{ trans('form.liability_direction') }}</th>
                                </template>
                                <template x-if="'liabilities' === objectType">
                                    <th data-column="liability_interest" :class="{'sortable': true, 'sortable_sorted': 'liability_interest' === sortColumn, 'sortable_sorted_asc': 'asc' === sortDirection, 'sortable_sorted_desc': 'desc' === sortDirection }">{{ trans('list.interest') }} ({{ trans('list.interest_period') }})</th>
                                </template>
                                <th data-column="account_number_and_iban" :class="{'sortable': true, 'sortable_sorted': 'account_number_and_iban' === sortColumn, 'sortable_sorted_asc': 'asc' === sortDirection, 'sortable_sorted_desc': 'desc' === sortDirection }">{{ trans('form.account_number') }}</th>
                                <template x-if="'liabilities' !== objectType">
                                    <th data-column="current_balance" :class="{'text-end': true, 'sortable': true, 'sortable_sorted': 'current_balance' === sortColumn, 'sortable_sorted_asc': 'asc' === sortDirection, 'sortable_sorted_desc': 'desc' === sortDirection }">{{ trans('list.currentBalance') }}</th>
                                </template>
                                <template x-if="'liabilities' === objectType">
                                    <th class="text-end">
                                        {{ trans('firefly.left_in_debt') }}
                                    </th>
                                </template>
                                <th {{-- hide on SM --}} class="d-md-table-cell d-none">{{ trans('list.active') }}</th>
                                {{-- hide last activity to make room for other stuff --}}
                                <template x-if="'liabilities' !== objectType">
                                    <th {{-- hide on LG and smaller. --}} class="d-lg-table-cell d-none">{{ trans('list.lastActivity') }}</th>
                                </template>
                                <th  {{-- hide on SM --}} class="w-15 d-md-table-cell d-none text-end">{{ trans('list.balanceDiff') }}</th>
                                <th>&nbsp;</th>
                            </tr>
                            </thead>
                            <tbody>
                            <template x-for="account in accounts" :key="account.id">
                                <tr :data-id="account.id">
                                    <td>
                                        <template x-if="('asset' === objectType || 'liabilities' === objectType) && 'asc' === sortDirection && 'order' === sortColumn && accounts.length > 1">
                                            <span class="btn btn-outline-secondary btn-sm bi bi-list object-handle"></span>
                                        </template>
                                    </td>
                                    <td>
                                        <a :href="'./accounts/show/' + account.id" :title="account.name" x-text="account.name"></a>
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
                                        <template x-if="0.0 === account.current_balance_float">
                                            <span class="money-neutral" x-text="account.current_balance"></span>
                                        </template>
                                        <template x-if="account.current_balance_float > 0.0">
                                            <span class="money-positive" x-text="account.current_balance"></span>
                                        </template>
                                        <template x-if="account.current_balance_float < 0.0">
                                            <span class="money-negative" x-text="account.current_balance"></span>
                                        </template>
                                    </td>
                                    </template>
                                    <template x-if="'liabilities' === objectType">
                                    <td class="text-end">
                                        <template x-if="0.0 === account.current_debt_float">
                                            <span class="money-neutral" x-text="account.current_debt"></span>
                                        </template>
                                        <template x-if="account.current_debt_float > 0.0">
                                            <span class="money-positive" x-text="account.current_debt"></span>
                                        </template>
                                        <template x-if="account.current_debt_float < 0.0">
                                            <span class="money-negative" x-text="account.current_debt"></span>
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
                                        <template x-if="0.0 === account.balance_difference_float">
                                            <span class="money-neutral" x-text="account.balance_difference"></span>
                                        </template>
                                        <template x-if="account.balance_difference_float > 0.0">
                                            <span class="money-positive" x-text="account.balance_difference"></span>
                                        </template>
                                        <template x-if="account.balance_difference_float < 0.0">
                                            <span class="money-negative" x-text="account.balance_difference"></span>
                                        </template>
                                    </td>
                                    <td class="justify-content-end">
                                        <div class="dropdown">
                                            <button class="btn btn-outline-secondary btn-sm dropdown-toggle" type="button" :id="'action_menu_' + account.id" data-bs-toggle="dropdown" aria-expanded="false">
                                                {{ __('firefly.actions') }}
                                            </button>
                                            <ul class="dropdown-menu" :aria-labelledby="'action_menu_' + account.id">
                                                <li><a class="dropdown-item" :href="'./accounts/edit/' + account.id + '?_from={{ urlencode($FF3_FROM) }}'"><span class="bi bi-pencil"></span> {{ __('firefly.edit') }}</a></li>
                                                <li><a class="dropdown-item" :href="'./accounts/delete/' + account.id + '?_from={{ urlencode($FF3_FROM) }}'"><span class="bi bi-trash"></span> {{ __('firefly.delete') }}</a></li>
                                                <template x-if="'asset' === objectType">
                                                    <li><a class="dropdown-item" :href="'./accounts/reconcile/' + account.id + '/index'"><span class="bi bi-check"></span> {{ __('firefly.reconcile_this_account') }}</a></li>
                                                </template>
                                            </ul>
                                        </div>
                                    </td>
                                </tr>
                            </template>
                            </tbody>
                        </table>

                        <template x-if="totalPages > 1">
                            <x-elements.alpine.page-navigation />
                        </template>
                    </div>
                    <x-elements.card-footer-with-menu :route="route('accounts.create', $objectType) . '?_from=' . urlencode($FF3_FROM)" :linkTitle="__('firefly.make_new_'. $objectType . '_account')" />
                </div>
            </div>
            <template x-if="0 === accounts.length && true === active && false === loading">
                <x-empty-page :route="route('accounts.create', [$objectType]) . '?_from=' . urlencode($FF3_FROM)" type="accounts" :object-type="$objectType" />
            </template>
            <template x-if="0 === accounts.length && true === active">
                <p class="text-center"><small>
                        <em>
                            <span x-text="i18next.t('firefly.may_inactive_accounts_link', {url: '{{ route('accounts.inactive.index', $objectType) }}'})"></span>
                        </em>
                    </small>
                </p>
            </template>
            <template x-if="0 !== accounts.length && true === active">
                <p class="text-center"><small>
                        <em>
                            <span x-text="i18next.t('firefly.inactive_account_link_js')"></span>
                        </em>
                    </small>
                </p>
            </template>
            <template x-if="0 !== accounts.length && false === active">
                <p class="text-center"><small>
                        <em>
                            <a href="{{ route('accounts.index', $objectType) }}"><span x-text="i18next.t('firefly.active_account_link')"></span></a>
                        </em>
                    </small>
                </p>
            </template>
            <template x-if="0 === accounts.length && false === active">
                <p class="text-center"><small>
                        <em>
                            <span x-text="i18next.t('firefly.no_inactive_accounts', {url: '{{ route('accounts.index', $objectType) }}'})"></span>
                        </em>
                    </small>
                </p>
            </template>
        </div>
@endsection
@section('scripts')
    @vite(['js/pages/accounts/index.js'])
@endsection
