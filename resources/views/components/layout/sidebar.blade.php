<ul
    class="nav sidebar-menu flex-column"
    data-lte-toggle="treeview"
    role="navigation"
    aria-label="Main navigation"
    data-accordion="false"
    id="navigation"
>
    <!-- <li class="nav-item menu-open"> -->
    <!-- <a href="route('index')" class="nav-link active"> -->
    <li class="nav-item">
        <a href="{{ route('index') }}" class="nav-link">
            <em class="nav-icon fa fa-gauge"></em>
            <p>{{ __('firefly.dashboard') }}</p>
        </a>
    </li>
    <li class="nav-header text-uppercase">{{ __('firefly.financial_control') }}</li>
    <li class="nav-item">
        <a href="{{ route('budgets.index') }}" class="nav-link">
            <em class="nav-icon fa fa-pie-chart"></em>
            <p>
                {{ __('firefly.budgets') }}
            </p>
        </a>
    </li>
    <li class="nav-item">
        <a href="{{ route('subscriptions.index') }}" class="nav-link">
            <em class="nav-icon fa fa-calendar"></em>
            <p>
                {{ __('firefly.subscriptions') }}
            </p>
        </a>
    </li>
    <li class="nav-item">
        <a href="{{ route('piggy-banks.index') }}" class="nav-link">
            <em class="nav-icon fa fa-bullseye"></em>
            <p>
                {{ __('firefly.piggyBanks') }}
            </p>
        </a>
    </li>
    <li class="nav-header text-uppercase">{{ __('firefly.accounting') }}</li>

    <li class="nav-item">
        <a href="#" class="nav-link">
            <em class="nav-icon fa fa-wallet"></em>
            <p>
                {{ __('firefly.transactions') }}
                <em class="nav-arrow fa fa-chevron-right"></em>
            </p>
        </a>
        <ul class="nav nav-treeview">
            <li class="nav-item">
                <a href="{{ route('transactions.index', ['withdrawal']) }}" class="nav-link">
                    <em class="nav-icon bi bi-arrow-left"></em>
                    <p>{{ __('firefly.expenses') }}</p>
                </a>
            </li>
            <li class="nav-item">
                <a href="{{ route('transactions.index', ['withdrawal']) }}" class="nav-link">
                    <em class="nav-icon bi bi-arrow-right"></em>
                    <p>{{ __('firefly.income') }}</p>
                </a>
            </li>
            <li class="nav-item">
                <a href="{{ route('transactions.index', ['transfers']) }}" class="nav-link">
                    <em class="nav-icon bi bi-arrow-left-right"></em>
                    <p>{{ __('firefly.transfers') }}</p>
                </a>
            </li>
            <li class="nav-item">
                <a href="{{ route('transactions.index', ['all']) }}" class="nav-link">
                    <em class="nav-icon bi bi-arrow-repeat"></em>
                    <p>{{ __('firefly.all_transactions') }}</p>
                </a>
            </li>
        </ul>
    </li>
    <li class="nav-item">
        <a href="#" class="nav-link">
            <em class="nav-icon bi bi-cpu"></em>
            <p>
                {{ __('firefly.automation') }}
                <i class="nav-arrow fa fa-chevron-right"></i>
            </p>
        </a>
        <ul class="nav nav-treeview">
            <li class="nav-item">
                <a href="{{ route('rules.index') }}" class="nav-link">
                    <em class="nav-icon bi bi-shuffle"></em>
                    <p>{{__('firefly.rules')}}</p>
                </a>
            </li>
            <li class="nav-item">
                <a href="{{ route('recurring.index') }}" class="nav-link">
                    <em class="nav-icon bi bi-paint-bucket"></em>
                    <p>{{__('firefly.recurrences') }}</p>
                </a>
            </li>
            @if(true === $featuringWebhooks)
            <li class="nav-item">
                <a href="{{ route('webhooks.index') }}" class="nav-link">
                    <em class="nav-icon bi bi-lightning"></em>
                    <p>{{ __('firefly.webhooks') }}</p>
                </a>
            </li>
            @endif
            @if(false === $featuringWebhooks)
                <li class="nav-item">
                    <a href="#" class="nav-link">
                        <em class="nav-icon bi bi-lightning"></em>
                        <p>{{ __('firefly.webhooks') }} ({{ trans('firefly.webhooks_menu_disabled') }})</p>
                    </a>
                </li>
            @endif
        </ul>
    </li>
    <li class="nav-header text-uppercase">{{ __('firefly.organization') }}</li>
    <li class="nav-item">
        <a href="#" class="nav-link">
            <em class="nav-icon bi bi-credit-card"></em>
            <p>
                {{__('firefly.accounts')}}
                <i class="nav-arrow fa fa-chevron-right"></i>
            </p>
        </a>
        <ul class="nav nav-treeview">
            <li class="nav-item">
                <a href="{{ route('accounts.index', ['asset']) }}" class="nav-link">
                    <i class="nav-icon bi bi-cash"></i>
                    <p>{{ __('firefly.asset_accounts') }}</p>
                </a>
            </li>
            <li class="nav-item">
                <a href="{{ route('accounts.index', ['expense']) }}" class="nav-link">
                    <i class="nav-icon bi bi-cart"></i>
                    <p>{{trans('firefly.expense_accounts')}}</p>
                </a>
            </li>
            <li class="nav-item">
                <a href="{{ route('accounts.index', ['revenue']) }}" class="nav-link">
                    <i class="nav-icon bi bi-box-arrow-down"></i>
                    <p>{{ __('firefly.revenue_accounts') }}</p>
                </a>
            </li>
            <li class="nav-item">
                <a href="{{ route('accounts.index', ['liabilities']) }}" class="nav-link">
                    <em class="nav-icon bi bi-ticket-detailed"></em>
                    <p>{{ __('firefly.liabilities_accounts') }}</p>
                </a>
            </li>
        </ul>
    </li>

    <li class="nav-item">
        <a href="#" class="nav-link">
            <em class="nav-icon bi bi-tags"></em>
            <p>
                {{trans('firefly.classification')}}
                <em class="nav-arrow fa fa-chevron-right"></em>
            </p>
        </a>
        <ul class="nav nav-treeview">
            <li class="nav-item">
                <a href="{{ route('categories.index') }}" class="nav-link">
                    <em class="nav-icon bi bi-bookmark"></em>
                    <p>{{trans('firefly.categories')}}</p>
                </a>
            </li>
            <li class="nav-item">
                <a href="{{ route('tags.index') }}" class="nav-link">
                    <em class="nav-icon bi bi-tag"></em>
                    <p>{{trans('firefly.tags')}}</p>
                </a>
            </li>
            <li class="nav-item">
                <a href="{{ route('object-groups.index') }}" class="nav-link">
                    <em class="nav-icon bi bi-envelope"></em>
                    <p>{{trans('firefly.object_groups_menu_bar')}}</p>
                </a>
            </li>
        </ul>
    </li>


    <li class="nav-header text-uppercase">{{ __('firefly.others') }}</li>
    <li class="nav-item">
        <a href="{{ route('currencies.index') }}" class="nav-link">
            <em class="nav-icon bi bi-currency-euro"></em>
            <p>{{ __('firefly.currencies') }}</p>
        </a>
    </li>
    <li class="nav-item">
        <a href="{{ route('exchange-rates.index') }}" class="nav-link">
            <em class="nav-icon bi bi-currency-exchange"></em>
            <p>{{ __('firefly.menu_exchange_rates_index') }}</p>
        </a>
    </li>
    <li class="nav-item">
        <a href="{{ route('reports.index') }}" class="nav-link">
            <em class="nav-icon bi bi-bar-chart"></em>
            <p>{{ __('firefly.reports') }}</p>
        </a>
    </li>
    <li class="nav-item">
        <a href="{{ route('export.index') }}" class="nav-link">
            <em class="nav-icon bi bi-upload"></em>
            <p>{{ __('firefly.export_data_menu') }}</p>
        </a>
    </li>

    @if('web' === $authGuard)
        <li class="nav-item">
            <a href="{{ route('logout') }}" class="nav-link logout-link">
                <em class="nav-icon bi bi-person-x text-danger"></em>
                <p>{{ __('firefly.logout') }}</p>
            </a>
        </li>
    @endif
    @if('remote_user_guard' === $authGuard && '' !== $logoutUrl)
        <li class="nav-item">
            <a href="{{ $logoutUrl }}" class="nav-link logout-link">
                <em class="nav-icon bi bi-person-x text-danger"></em>
                <p>{{ __('firefly.logout') }}</p>
            </a>
        </li>
    @endif
</ul>
