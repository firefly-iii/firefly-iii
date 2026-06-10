<!-- begin create menu -->
<li class="nav-item dropdown">
    <a class="nav-link" id="create-menu" data-bs-toggle="dropdown" href="#" aria-expanded="false">
        <em class="bi bi-plus-circle"></em>
    </a>
    <div class="dropdown-menu dropdown-menu-lg dropdown-menu-end">
        <a href="{{ route('transactions.create', ['withdrawal']) }}" class="dropdown-item">
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
        <a href="{{ route('transactions.create', ['deposit']) }}" class="dropdown-item">
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
        <a href="{{ route('transactions.create', ['transfer']) }}" class="dropdown-item">
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

        <a href="{{ route('accounts.create', ['asset']) }}" class="dropdown-item">
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
        <a href="{{ route('accounts.create', ['liabilities']) }}" class="dropdown-item">
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

        <a href="{{ route('budgets.create') }}" class="dropdown-item">
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
        <a href="{{ route('categories.create') }}" class="dropdown-item">
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
        <a href="{{ route('piggy-banks.create') }}" class="dropdown-item">
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
        <a href="{{ route('subscriptions.create') }}" class="dropdown-item">
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
        <a href="{{ route('rules.create') }}" class="dropdown-item">
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
        <a href="{{ route('recurring.create') }}" class="dropdown-item">
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
        <a href="{{ route('webhooks.create') }}" class="dropdown-item">
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
