<!-- begin create menu -->
<li class="nav-item dropdown">
    <a class="nav-link" id="create-menu" data-bs-toggle="dropdown" href="#" aria-expanded="false">
        <em class="bi bi-plus-circle"></em>
    </a>
    <div class="dropdown-menu dropdown-menu-lg dropdown-menu-end">



        <a href="{{ route('transactions.create', ['withdrawal']) }}?_from={{ urlencode($FF3_FROM) }}" class="dropdown-item">
            <div class="d-flex">
                <div class="grow">
                    <h3 class="dropdown-item-title">
                        <!-- withdrawal, deposit, transfer -->
                        <em class="bi bi-arrow-left me-2"></em>
                        {{ __('firefly.create_new_withdrawal') }}
                    </h3>
                </div>
            </div>
        </a>
        <div class="dropdown-divider"></div>
        <a href="{{ route('transactions.create', ['deposit']) }}?_from={{ urlencode($FF3_FROM) }}" class="dropdown-item">
            <div class="d-flex">
                <div class="grow">
                    <h3 class="dropdown-item-title">
                        <!-- withdrawal, deposit, transfer -->
                        <em class="bi bi-arrow-right me-2"></em>
                        {{ __('firefly.create_new_deposit') }}
                    </h3>
                </div>
            </div>
        </a>
        <div class="dropdown-divider"></div>
        <a href="{{ route('transactions.create', ['transfer']) }}?_from={{ urlencode($FF3_FROM) }}" class="dropdown-item">
            <div class="d-flex">
                <div class="grow">
                    <h3 class="dropdown-item-title">
                        <!-- withdrawal, deposit, transfer -->
                        <em class="bi bi-arrow-left-right me-2"></em>
                        {{ __('firefly.create_new_transfer') }}
                    </h3>
                </div>
            </div>
        </a>
        <div class="dropdown-divider"></div>

        <a href="{{ route('accounts.create', ['asset']) }}?_from={{ urlencode($FF3_FROM) }}" class="dropdown-item">
            <div class="d-flex">
                <div class="grow">
                    <h3 class="dropdown-item-title">
                        <!-- withdrawal, deposit, transfer -->
                        <em class="bi bi-cash me-2"></em>
                        {{ __('firefly.create_new_asset') }}
                    </h3>
                </div>
            </div>
        </a>
        <div class="dropdown-divider"></div>
        <a href="{{ route('accounts.create', ['liabilities']) }}?_from={{ urlencode($FF3_FROM) }}" class="dropdown-item">
            <div class="d-flex">
                <div class="grow">
                    <h3 class="dropdown-item-title">
                        <!-- withdrawal, deposit, transfer -->
                        <em class="bi bi-ticket-detailed me-2"></em>
                        {{ __('firefly.create_new_liabilities') }}
                    </h3>
                </div>
            </div>
        </a>
        <div class="dropdown-divider"></div>

        <a href="{{ route('budgets.create') }}?_from={{ urlencode($FF3_FROM) }}" class="dropdown-item">
            <div class="d-flex">
                <div class="grow">
                    <h3 class="dropdown-item-title">
                        <!-- withdrawal, deposit, transfer -->
                        <em class="bi bi-pie-chart me-2"></em>
                        {{ __('firefly.create_new_budget') }}
                    </h3>
                </div>
            </div>
        </a>
        <div class="dropdown-divider"></div>
        <a href="{{ route('categories.create') }}?_from={{ urlencode($FF3_FROM) }}" class="dropdown-item">
            <div class="d-flex">
                <div class="grow">
                    <h3 class="dropdown-item-title">
                        <!-- withdrawal, deposit, transfer -->
                        <em class="bi bi-bookmark me-2"></em>
                        {{ __('firefly.create_new_category') }}
                    </h3>
                </div>
            </div>
        </a>
        <div class="dropdown-divider"></div>
        <a href="{{ route('piggy-banks.create') }}?_from={{ urlencode($FF3_FROM) }}" class="dropdown-item">
            <div class="d-flex">
                <div class="grow">
                    <h3 class="dropdown-item-title">
                        <!-- withdrawal, deposit, transfer -->
                        <em class="bi bi-bullseye me-2"></em>
                        {{ __('firefly.create_new_piggy_bank') }}
                    </h3>
                </div>
            </div>
        </a>
        <div class="dropdown-divider"></div>
        <a href="{{ route('subscriptions.create') }}?_from={{ urlencode($FF3_FROM) }}" class="dropdown-item">
            <div class="d-flex">
                <div class="grow">
                    <h3 class="dropdown-item-title">
                        <!-- withdrawal, deposit, transfer -->
                        <em class="bi bi-calendar me-2"></em>
                        {{ __('firefly.create_new_subscription') }}
                    </h3>
                </div>
            </div>
        </a>
        <div class="dropdown-divider"></div>
        <a href="{{ route('rules.create') }}?_from={{ urlencode($FF3_FROM) }}" class="dropdown-item">
            <div class="d-flex">
                <div class="grow">
                    <h3 class="dropdown-item-title">
                        <!-- withdrawal, deposit, transfer -->
                        <em class="bi bi-shuffle me-2"></em>
                        {{ __('firefly.create_new_rule') }}
                    </h3>
                </div>
            </div>
        </a>
        <div class="dropdown-divider"></div>
        <a href="{{ route('recurring.create') }}?_from={{ urlencode($FF3_FROM) }}" class="dropdown-item">
            <div class="d-flex">
                <div class="grow">
                    <h3 class="dropdown-item-title">
                        <!-- withdrawal, deposit, transfer -->
                        <em class="bi bi-paint-bucket me-2"></em>
                        {{ __('firefly.create_new_recurrence') }}
                    </h3>
                </div>
            </div>
        </a>
        <div class="dropdown-divider"></div>
        <a href="{{ route('webhooks.create') }}?_from={{ urlencode($FF3_FROM) }}" class="dropdown-item">
            <div class="d-flex">
                <div class="grow">
                    <h3 class="dropdown-item-title">
                        <!-- withdrawal, deposit, transfer -->
                        <em class="bi bi-lightning me-2"></em>
                        {{ __('firefly.create_new_webhook') }}
                    </h3>
                </div>
            </div>
        </a>
    </div>
</li>
<!-- end create menu -->
