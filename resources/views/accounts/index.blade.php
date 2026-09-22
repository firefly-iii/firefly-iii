@extends('layout.v3.session')
@section('content')
    @if(count($accounts) > 0)
        <div class="row" x-data="index">
            <div class="col-lg-12 col-md-12 col-sm-12">
                <div class="card" id="account-index-{{ $objectType }}">
                    <x-elements.card-header-with-menu :cardTitle="trans('firefly.'.$objectType.'_accounts')" :route="route('accounts.create', $objectType) . '?_from=' . urlencode($FF3_FROM)" :linkTitle="__('firefly.make_new_'. $objectType . '_account')"/>
                    <div class="card-body p-0">
                        {{--
                        <x-lists.accounts :accounts="$accounts" :objectType="$objectType" :page="$page ?? 1" />
                        --}}
                        <div class="m-2">
                            TODO Pagination be here.
                        </div>
                        <table class="table table-valign-middle table-sm table-hover sortable">
                            <thead>
                            <tr>
                                <th data-column="order" :class="{'w-5' : true,'sortable': true, 'sortable_sorted': 'order' === sortColumn, 'sortable_sorted_asc': 'asc' === sortDirection, 'sortable_sorted_desc': 'desc' === sortDirection }">&nbsp;</th>
                                <th data-column="name"  :class="{'w-20' : true,'sortable': true, 'sortable_sorted': 'name' === sortColumn, 'sortable_sorted_asc': 'asc' === sortDirection, 'sortable_sorted_desc': 'desc' === sortDirection }">{{ trans('list.name') }}</th>
                                <template x-if="'asset' === objectType">
                                    <th {{-- hide on LG and smaller. --}} class="d-lg-table-cell d-none">{{ trans('list.role') }}</th>
                                </template>
                                <template x-if="'liabilities' === objectType">
                                    <th>{{ trans('list.liability_type') }}</th>
                                </template>
                                <template x-if="'liabilities' === objectType">
                                    <th>{{ trans('form.liability_direction') }}</th>
                                </template>
                                <template x-if="'liabilities' === objectType">
                                    <th>{{ trans('list.interest') }} ({{ trans('list.interest_period') }})</th>
                                </template>
                                <th>{{ trans('form.account_number') }}</th>
                                <template x-if="'liabilities' === objectType">
                                    <th class="text-end">{{ trans('list.currentBalance') }}</th>
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
                        </table>


                        <div class="m-2">
                            TODO Pagination be here.
                        </div>

                    </div>
                    <x-elements.card-footer-with-menu :route="route('accounts.create', $objectType) . '?_from=' . urlencode($FF3_FROM)" :linkTitle="__('firefly.make_new_'. $objectType . '_account')" />
                </div>
                @if($inactiveCount > 0 && !$inactivePage)
                    <p class="m-2"><small>
                            <em>
                                <a href="{{ route('accounts.inactive.index', $objectType) }}" class="text-muted">
                                    {{ trans_choice('firefly.inactive_account_link', $inactiveCount) }}
                                </a>
                            </em>
                        </small>
                    </p>
                @endif
                @if($inactivePage)
                    <p class="m-2"><small class="text-muted">
                            <em>
                                {{ trans('firefly.all_accounts_inactive') }}
                                <a href="{{ route('accounts.index', $objectType) }}">
                                    {{ trans('firefly.active_account_link', ['count' => $inactiveCount]) }}
                                </a>
                            </em>
                        </small>
                    </p>
                @endif
            </div>
        </div>
    @endif
    @if(0 === count($accounts) && 1 === $page)
        @php
            $shownDemo = true
        @endphp
        <x-empty-page :route="route('accounts.create', [$objectType]) . '?_from=' . urlencode($FF3_FROM)" type="accounts" :object-type="$objectType" />
        @if($inactiveCount > 0)
            <p class="text-center"><small>
                    <em>
                        <a href="{{ route('accounts.inactive.index', $objectType) }}" class="text-muted">
                            {{ trans_choice('firefly.inactive_account_link', $inactiveCount) }}
                        </a>
                    </em>
                </small>
            </p>
        @endif

    @endif
@endsection
@section('scripts')
    @vite(['js/pages/accounts/index.js'])
@endsection
