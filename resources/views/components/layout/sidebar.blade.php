<ul
    class="nav sidebar-menu flex-column"
    data-lte-toggle="treeview"
    role="navigation"
    aria-label="Main navigation"
    data-accordion="false"
    id="navigation"
    x-data="sidebar"
>
    <li class="nav-item search-xl">
        <form action="{{ route('search.index') }}" method="get" class="pb-2">
            <div class="form-row align-items-center">
                <div class="col-auto">
                    <div class="input-group mb-2">
                    <label class="sr-only" for="searchInput">{{ trans('firefly.searchPlaceholder') }}</label>
                    <input autocomplete="off" type="text" id="searchInput" name="search" class="form-control" placeholder="{{ trans('firefly.searchPlaceholder') }}" value="{{ $query ?? '' }}" spellcheck="false">
                        <div class="input-group-append">
                            <button type='submit' name='go' id='search-btn' class="btn btn-outline-secondary"><span class="bi bi-search"></span></button>
                        </div>
                </div>
            </div>
            </div>
        </form>
    </li>
    <li class="nav-item search-xs" style="display:none;">
        <a title="{{ __('firefly.search') }}" href="{{ route('search.index') }}" class="nav-link {{ menu_item_active('search.index') }}">
            <em title="{{ __('firefly.search') }}" class="nav-icon bi bi-search"></em>
            <p>{{ __('firefly.search') }}</p>
        </a>
    </li>
    <li class="nav-item">
        <a title="{{ __('firefly.dashboard') }}" href="{{ route('index') }}" class="nav-link {{ menu_item_active('index') }}">
            <em title="{{ __('firefly.dashboard') }}" class="nav-icon bi bi-speedometer"></em>
            <p>{{ __('firefly.dashboard') }}</p>
        </a>
    </li>
    <li class="nav-header text-uppercase">{{ __('firefly.financial_control') }}</li>
    <li class="nav-item">
        <a title="{{ __('firefly.budgets') }}" href="{{ route('budgets.index') }}" class="nav-link {{ menu_item_active_partial('budgets.') }}">
            <em title="{{ __('firefly.budgets') }}" class="nav-icon bi bi-pie-chart"></em>
            <p>
                {{ __('firefly.budgets') }}
            </p>
        </a>
    </li>
    <li class="nav-item">
        <a title="{{ __('firefly.subscriptions') }}" href="{{ route('subscriptions.index') }}" class="nav-link {{ menu_item_active_partial('subscriptions.') }}">
            <em title="{{ __('firefly.subscriptions') }}" class="nav-icon bi bi-calendar"></em>
            <p>
                {{ __('firefly.subscriptions') }}
            </p>
        </a>
    </li>
    <li class="nav-item">
        <a title="{{ __('firefly.piggyBanks') }}" href="{{ route('piggy-banks.index') }}" class="nav-link {{ menu_item_active_partial('piggy-banks.') }}">
            <em title="{{ __('firefly.piggyBanks') }}" class="nav-icon bi bi-piggy-bank"></em>
            <p>
                {{ __('firefly.piggyBanks') }}
            </p>
        </a>
    </li>
    <li class="nav-header text-uppercase">{{ __('firefly.accounting') }}</li>

    <li class="nav-item {{ menu_open_partial('transactions.') }}">
        <a title="{{ __('firefly.transactions') }}" href="#" class="nav-link">
            <em title="{{ __('firefly.transactions') }}" class="nav-icon bi bi-wallet"></em>
            <p>
                {{ __('firefly.transactions') }}
                <em class="nav-arrow bi bi-chevron-right"></em>
            </p>
        </a>
        <ul class="nav nav-treeview">
            <li class="nav-item sub-nav-item">
                <a title="{{ __('firefly.expenses') }}" href="{{ route('transactions.index', ['withdrawal']) }}" class="nav-link {{ menu_sub_item_active('transactions.index','withdrawal') }}">
                    <em title="{{ __('firefly.expenses') }}" class="nav-icon bi bi-arrow-left"></em>
                    <p>{{ __('firefly.expenses') }}</p>
                </a>
            </li>
            <li class="nav-item sub-nav-item">
                <a title="{{ __('firefly.income') }}" href="{{ route('transactions.index', ['deposit']) }}" class="nav-link {{ menu_sub_item_active('transactions.index','deposit') }}">
                    <em title="{{ __('firefly.income') }}" class="nav-icon bi bi-arrow-right"></em>
                    <p>{{ __('firefly.income') }}</p>
                </a>
            </li>
            <li class="nav-item sub-nav-item">
                <a title="{{ __('firefly.transfers') }}" href="{{ route('transactions.index', ['transfers']) }}" class="nav-link {{ menu_sub_item_active('transactions.index','transfers') }}">
                    <em title="{{ __('firefly.transfers') }}" class="nav-icon bi bi-arrow-left-right"></em>
                    <p>{{ __('firefly.transfers') }}</p>
                </a>
            </li>
            <li class="nav-item sub-nav-item">
                <a title="{{ __('firefly.all_transactions') }}" href="{{ route('transactions.index', ['all']) }}" class="nav-link {{ menu_sub_item_active('transactions.index','all') }}">
                    <em title="{{ __('firefly.all_transactions') }}" class="nav-icon bi bi-arrow-repeat"></em>
                    <p>{{ __('firefly.all_transactions') }}</p>
                </a>
            </li>
        </ul>
    </li>
    <li class="nav-item {{ menu_open_partial('rules.') }} {{ menu_open_partial('rule-groups.') }} {{ menu_open_partial('webhooks.') }}  {{ menu_open_partial('recurring.') }}">
        <a href="#" class="nav-link" title="{{ __('firefly.automation') }}">
            <em title="{{ __('firefly.automation') }}" class="nav-icon bi bi-cpu"></em>
            <p>
                {{ __('firefly.automation') }}
                <em class="nav-arrow bi bi-chevron-right"></em>
            </p>
        </a>
        <ul class="nav nav-treeview">
            <li class="nav-item sub-nav-item">
                <a title="{{__('firefly.rules')}}" href="{{ route('rules.index') }}" class="nav-link {{ menu_item_active_partial('rules.') }} {{ menu_item_active_partial('rule-groups.') }}">
                    <em title="{{__('firefly.rules')}}" class="nav-icon bi bi-shuffle"></em>
                    <p>{{__('firefly.rules')}}</p>
                </a>
            </li>
            <li class="nav-item sub-nav-item">
                <a title="{{__('firefly.recurrences') }}" href="{{ route('recurring.index') }}" class="nav-link {{ menu_item_active_partial('recurring.') }}">
                    <em title="{{__('firefly.recurrences') }}" class="nav-icon bi bi-paint-bucket"></em>
                    <p>{{__('firefly.recurrences') }}</p>
                </a>
            </li>
            @if(true === $featuringWebhooks)
            <li class="nav-item sub-nav-item">
                <a title="{{ __('firefly.webhooks') }}" href="{{ route('webhooks.index') }}" class="nav-link {{ menu_item_active_partial('webhooks.') }}">
                    <em title="{{ __('firefly.webhooks') }}" class="nav-icon bi bi-lightning"></em>
                    <p>{{ __('firefly.webhooks') }}</p>
                </a>
            </li>
            @endif
            @if(false === $featuringWebhooks)
                <li class="nav-item sub-nav-item">
                    <span title="{{ __('firefly.webhooks') }} " class="nav-link">
                        <em title="{{ __('firefly.webhooks') }} " class="nav-icon bi bi-lightning"></em>
                        <p>{{ __('firefly.webhooks') }} ({{ trans('firefly.webhooks_menu_disabled') }})</p>
                    </span>
                </li>
            @endif
        </ul>
    </li>
    <li class="nav-header text-uppercase">{{ __('firefly.organization') }}</li>
    <li class="nav-item {{ menu_open_partial('accounts.') }}">
        <a title="{{__('firefly.accounts')}}" href="#" class="nav-link">
            <em title="{{__('firefly.accounts')}}" class="nav-icon bi bi-credit-card"></em>
            <p>
                {{__('firefly.accounts')}}
                <em class="nav-arrow bi bi-chevron-right"></em>
            </p>
        </a>
        <ul class="nav nav-treeview">
            <li class="nav-item sub-nav-item">
                <a title="{{ __('firefly.asset_accounts') }}" href="{{ route('accounts.index', ['asset']) }}" class="nav-link {{ menu_sub_item_active('accounts.index','asset') }}">
                    <em title="{{ __('firefly.asset_accounts') }}" class="nav-icon bi bi-cash"></em>
                    <p>{{ __('firefly.asset_accounts') }}</p>
                </a>
            </li>
            <li class="nav-item sub-nav-item">
                <a title="{{trans('firefly.expense_accounts')}}" href="{{ route('accounts.index', ['expense']) }}" class="nav-link {{ menu_sub_item_active('accounts.index','expense') }}">
                    <em title="{{trans('firefly.expense_accounts')}}" class="nav-icon bi bi-cart"></em>
                    <p>{{trans('firefly.expense_accounts')}}</p>
                </a>
            </li>
            <li class="nav-item sub-nav-item">
                <a title="{{ __('firefly.revenue_accounts') }}" href="{{ route('accounts.index', ['revenue']) }}" class="nav-link {{ menu_sub_item_active('accounts.index','revenue') }}">
                    <em title="{{ __('firefly.revenue_accounts') }}" class="nav-icon bi bi-box-arrow-down"></em>
                    <p>{{ __('firefly.revenue_accounts') }}</p>
                </a>
            </li>
            <li class="nav-item sub-nav-item">
                <a title="{{ __('firefly.liabilities_accounts') }}" href="{{ route('accounts.index', ['liabilities']) }}" class="nav-link {{ menu_sub_item_active('accounts.index','liabilities') }}">
                    <em title="{{ __('firefly.liabilities_accounts') }}" class="nav-icon bi bi-ticket-detailed"></em>
                    <p>{{ __('firefly.liabilities_accounts') }}</p>
                </a>
            </li>
        </ul>
    </li>

    <li class="nav-item {{ menu_open_partial('categories.') }} {{ menu_open_partial('tags.') }} {{ menu_open_partial('object-groups.') }}">
        <a title="{{trans('firefly.classification')}}" href="#" class="nav-link">
            <em title="{{trans('firefly.classification')}}" class="nav-icon bi bi-tags"></em>
            <p>
                {{trans('firefly.classification')}}
                <em class="nav-arrow bi bi-chevron-right"></em>
            </p>
        </a>
        <ul class="nav nav-treeview">
            <li class="nav-item sub-nav-item">
                <a title="{{trans('firefly.categories')}}" ref="{{ route('categories.index') }}" class="nav-link {{ menu_item_active_partial('categories.') }}">
                    <em title="{{trans('firefly.categories')}}" class="nav-icon bi bi-bookmark"></em>
                    <p>{{trans('firefly.categories')}}</p>
                </a>
            </li>
            <li class="nav-item sub-nav-item">
                <a title="{{trans('firefly.tags')}}" href="{{ route('tags.index') }}" class="nav-link {{ menu_item_active_partial('tags.') }}">
                    <em title="{{trans('firefly.tags')}}" class="nav-icon bi bi-tag"></em>
                    <p>{{trans('firefly.tags')}}</p>
                </a>
            </li>
            <li class="nav-item sub-nav-item">
                <a title="{{trans('firefly.object_groups_menu_bar')}}" href="{{ route('object-groups.index') }}" class="nav-link {{ menu_item_active_partial('object-groups.') }}">
                    <em title="{{trans('firefly.object_groups_menu_bar')}}" class="nav-icon bi bi-envelope"></em>
                    <p>{{trans('firefly.object_groups_menu_bar')}}</p>
                </a>
            </li>
        </ul>
    </li>


    <li class="nav-header text-uppercase">{{ __('firefly.others') }}</li>
    <li class="nav-item">
        <a title="{{ __('firefly.currencies') }}" href="{{ route('currencies.index') }}" class="nav-link {{ menu_item_active_partial('currencies.') }}">
            <em title="{{ __('firefly.currencies') }}" class="nav-icon bi bi-currency-euro"></em>
            <p>{{ __('firefly.currencies') }}</p>
        </a>
    </li>
    @if(true === $featuringCer)
    <li class="nav-item">
        <a title="{{ __('firefly.menu_exchange_rates_index') }}" href="{{ route('exchange-rates.index') }}" class="nav-link {{ menu_item_active_partial('exchange-rates.') }}">
            <em title="{{ __('firefly.menu_exchange_rates_index') }}" class="nav-icon bi bi-currency-exchange"></em>
            <p>{{ __('firefly.menu_exchange_rates_index') }}</p>
        </a>
    </li>
    @endif
    @if(false === $featuringCer)
        <li class="nav-item">
            <span title="{{ __('firefly.menu_exchange_rates_index') }}" class="nav-link {{ menu_item_active_partial('exchange-rates.') }}">
                <em title="{{ __('firefly.menu_exchange_rates_index') }}" class="nav-icon bi bi-currency-exchange"></em>
                <p>{{ __('firefly.menu_exchange_rates_index') }} ({{ trans('firefly.cer_menu_disabled') }})</p>
            </span>
        </li>
    @endif
    <li class="nav-item">
        <a title="{{ __('firefly.reports') }}" href="{{ route('reports.index') }}" class="nav-link {{ menu_item_active_partial('reports.') }}">
            <em title="{{ __('firefly.reports') }}" class="nav-icon bi bi-bar-chart"></em>
            <p>{{ __('firefly.reports') }}</p>
        </a>
    </li>
    <li class="nav-item">
        <a title="{{ __('firefly.export_data_menu') }}" href="{{ route('export.index') }}" class="nav-link {{ menu_item_active_partial('export.') }}">
            <em title="{{ __('firefly.export_data_menu') }}" class="nav-icon bi bi-upload"></em>
            <p>{{ __('firefly.export_data_menu') }}</p>
        </a>
    </li>

    @if('web' === $authGuard)
        <li class="nav-item">
            <a title="{{ __('firefly.logout') }}" href="{{ route('logout') }}" @click="logoutUser" class="nav-link logout-link">
                <em title="{{ __('firefly.logout') }}" class="nav-icon bi-box-arrow-left money-negative"></em>
                <p>{{ __('firefly.logout') }}</p>
            </a>
        </li>
    @endif
    @if('remote_user_guard' === $authGuard && '' !== $logoutUrl)
        <li class="nav-item">
            <a title="{{ __('firefly.logout') }}" href="{{ $logoutUrl }}" class="nav-link logout-link">
                <em title="{{ __('firefly.logout') }}" class="nav-icon bi-box-arrow-left money-negative"></em>
                <p>{{ __('firefly.logout') }}</p>
            </a>
        </li>
    @endif
</ul>
