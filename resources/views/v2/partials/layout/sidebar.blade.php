<aside class="app-sidebar bg-body-secondary shadow" data-bs-theme="dark">
    <!--begin::Sidebar Brand-->
    <div class="sidebar-brand">
        <!--begin::Brand Link-->
        <a href="{{route('index') }}" class="brand-link">
            <!--begin::Brand Image-->
            <img src="v2/i/logo.png" alt="Firefly III Logo"
                 class="brand-image opacity-75 shadow">
            <!--end::Brand Image-->
            <!--begin::Brand Text-->
            <span class="brand-text fw-light">Firefly III</span>
            <!--end::Brand Text-->
        </a>
        <!--end::Brand Link-->
    </div>
    <!--end::Sidebar Brand-->
    <!--begin::Sidebar Wrapper-->

    <div class="sidebar-wrapper">
        <nav class="mt-2">
            <!--begin::Sidebar Menu-->
            <ul class="nav sidebar-menu flex-column" data-lte-toggle="treeview" role="menu"
                data-accordion="false">
                <li class="nav-item menu-open">
                    <a href="{{ route('index') }}" class="nav-link active">
                        <em class="nav-icon fa-solid fa-gauge-high"></em>
                        <p>
                            {{ __('firefly.dashboard')  }}
                        </p>
                    </a>
                </li>
                <li class="nav-header">{{ strtoupper(__('firefly.financial_control'))  }}</li>
                <li class="nav-item">
                    <a href="{{ route('budgets.index')  }}" class="nav-link">
                        <em class="nav-icon fa-solid fa-chart-pie"></em>
                        <p>{{ __('firefly.budgets')  }}</p>
                    </a>
                </li>
                <li class="nav-item">
                    <a href="{{ route('subscriptions.index') }}" class="nav-link">
                        <i class="nav-icon fa-regular fa-calendar"></i>
                        <p>{{ __('firefly.subscriptions')  }}</p>
                    </a>
                </li>
                <li class="nav-item">
                    <a href="{{ route('piggy-banks.index') }}" class="nav-link">
                        <em class="nav-icon fa-solid fa-piggy-bank"></em>
                        <p>{{ __('firefly.piggy_banks')  }}</p>
                    </a>
                </li>
                <li class="nav-header">{{ strtoupper(__('firefly.accounting'))  }}</li>
                <li class="nav-item">
                    <a href="#" class="nav-link">
                        <em class="nav-icon fa-solid fa-arrow-right-arrow-left"></em>
                        <p>
                            {{ __('firefly.transactions') }}
                            <i class="nav-arrow fa-solid fa-chevron-right"></i>
                        </p>
                    </a>
                    <ul class="nav nav-treeview">
                        <li class="nav-item">
                            <a href="{{ route('transactions.index',['withdrawal']) }}" class="nav-link">
                                <em class="nav-icon fa-solid fa-arrow-left"></em>
                                <p>{{ __('firefly.expenses')  }}</p>
                            </a>
                        </li>
                        <li class="nav-item">
                            <a href="{{ route('transactions.index', ['deposit']) }}" class="nav-link">
                                <em class="nav-icon fa-solid fa-arrow-right"></em>
                                <p>{{ __('firefly.income') }}</p>
                            </a>
                        </li>
                        <li class="nav-item">
                            <a href="{{ route('transactions.index', ['transfers']) }}" class="nav-link">
                                <i class="nav-icon fa-solid fa-arrows-rotate"></i>
                                <p>{{ __('firefly.transfers') }}</p>
                            </a>
                        </li>
                        <li class="nav-item">
                            <a href="{{ route('transactions.index', ['all']) }}" class="nav-link">
                                <i class="nav-icon fa-solid fa-arrows-turn-to-dots"></i>
                                <p>{{ __('firefly.all_transactions') }}</p>
                            </a>
                        </li>
                    </ul>
                </li>
                <li class="nav-item">
                    <a href="#" class="nav-link">
                        <i class="nav-icon fa-solid fa-microchip"></i>
                        <p>
                            {{ __('firefly.automation') }}
                            <i class="nav-arrow fa-solid fa-chevron-right"></i>
                        </p>
                    </a>
                    <ul class="nav nav-treeview">
                        <li class="nav-item">
                            <a href="{{ route('rules.index') }}" class="nav-link">
                                <i class="nav-icon fa-solid fa-shuffle"></i>
                                <p>{{ __('firefly.rules') }}</p>
                            </a>
                        </li>
                        <li class="nav-item">
                            <a href="{{ route('recurring.index') }}" class="nav-link">
                                <i class="nav-icon fa-solid fa-repeat"></i>
                                <p>{{ __('firefly.recurrences') }}</p>
                            </a>
                        </li>
                        <li class="nav-item">
                            <a href="{{ route('webhooks.index') }}" class="nav-link">
                                <i class="nav-icon fa-solid fa-bolt-lightning"></i>
                                <p>{{ __('firefly.webhooks') }}</p>
                            </a>
                        </li>
                    </ul>
                </li>
                <li class="nav-header">{{ strtoupper(__('firefly.others'))  }}</li>
                <li class="nav-item">
                    <a href="#" class="nav-link">
                        <i class="nav-icon fa-regular fa-credit-card"></i>
                        <p>
                            {{ __('firefly.accounts') }}
                            <i class="nav-arrow fa-solid fa-chevron-right"></i>
                        </p>
                    </a>
                    <ul class="nav nav-treeview">
                        <li class="nav-item">
                            <a href="{{ route('accounts.index', ['asset']) }}" class="nav-link">
                                <i class="nav-icon fa-solid fa-money-bills"></i>
                                <p>{{ __('firefly.asset_accounts') }}</p>
                            </a>
                        </li>
                        <li class="nav-item">
                            <a href="{{ route('accounts.index', ['expense']) }}" class="nav-link">
                                <i class="nav-icon fa-solid fa-cart-shopping"></i>
                                <p>{{ __('firefly.expense_accounts') }}</p>
                            </a>
                        </li>
                        <li class="nav-item">
                            <a href="{{ route('accounts.index', ['revenue']) }}" class="nav-link">
                                <i class="nav-icon fa-solid fa-money-bill-trend-up"></i>
                                <p>{{ __('firefly.revenue_accounts') }}</p>
                            </a>
                        </li>
                        <li class="nav-item">
                            <a href="{{ route('accounts.index', ['liabilities']) }}" class="nav-link">
                                <i class="nav-icon fa-solid fa-landmark"></i>
                                <p>{{ __('firefly.liabilities') }}</p>
                            </a>
                        </li>
                    </ul>
                </li>
                <li class="nav-item">
                    <a href="#" class="nav-link">
                        <i class="nav-icon fa-solid fa-tags"></i>
                        <p>
                            {{ __('firefly.classification') }}
                            <i class="nav-arrow fa-solid fa-chevron-right"></i>
                        </p>
                    </a>
                    <ul class="nav nav-treeview">
                        <li class="nav-item">
                            <a href="{{ route('categories.index') }}" class="nav-link">
                                <i class="nav-icon fa-regular fa-bookmark"></i>
                                <p>{{ __('firefly.categories') }}</p>
                            </a>
                        </li>
                        <li class="nav-item">
                            <a href="{{ route('tags.index') }}" class="nav-link">
                                <i class="nav-icon fa-solid fa-tag"></i>
                                <p>{{ __('firefly.tags') }}</p>
                            </a>
                        </li>
                        <li class="nav-item">
                            <a href="{{ route('object-groups.index') }}" class="nav-link">
                                <i class="nav-icon fa-regular fa-envelope"></i>
                                <p>{{ __('firefly.object_groups') }}</p>
                            </a>
                        </li>
                    </ul>
                </li>

                <li class="nav-item">
                    <a href="{{ route('reports.index') }}" class="nav-link">
                        <i class="nav-icon fa-solid fa-chart-column"></i>
                        <p>{{ __('firefly.reports') }}</p>
                    </a>
                </li>
                <li class="nav-item">
                    <a href="{{ route('export.index') }}" class="nav-link">
                        <i class="nav-icon fa-solid fa-upload"></i>
                        <p>{{ __('firefly.export_data_menu') }}</p>
                    </a>
                </li>

                <li class="nav-item">
                    <a href="{{ route('logout') }}" class="nav-link logout-link">
                        <i class="nav-icon fa-solid fa-arrow-right-from-bracket"></i>
                        <p>TODO {{ __('firefly.logout') }}</p>
                    </a>
                </li>
            </ul>
            <!--end::Sidebar Menu-->
        </nav>
    </div>
    <!--end::Sidebar Wrapper-->
</aside>

<!-- simple script for logout thing -->
