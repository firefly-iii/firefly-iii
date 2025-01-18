<li class="nav-item">
    <a class="nav-link" href="{{ route(Route::current()->getName(), Route::current()->parameters()) }}?force_default_layout=true">
        <i class="fa-solid fa-landmark"></i>
    </a>
</li>
<li class="nav-item toggle-page-internals d-none">
    <a class="nav-link" href="#" data-bs-toggle="modal" data-bs-target="#internalsModal">
        <em class="fa-solid fa-sliders"></em>
    </a>
</li>
<li class="nav-item toggle-page-wizard d-none">
    <a class="nav-link" href="#" data-bs-toggle="modal" data-bs-target="#wizardModal">
        <em class="fa-solid fa-hat-wizard"></em>
    </a>
</li>
<li class="nav-item dropdown">
    <a class="nav-link" data-bs-toggle="dropdown" href="#">
        <i class="fa-solid fa-gears"></i>
    </a>
    <div class="dropdown-menu dropdown-menu-lg dropdown-menu-end">
        <a href="{{ route('settings.index') }}" class="dropdown-item">
            <em class="fa-regular fa-user me-2 fa-fw"></em>
            {{ __('firefly.system_settings') }}
        </a>
        <div class="dropdown-divider"></div>
        <a href="{{ route('currencies.index') }}" class="dropdown-item">
            <em class="fa-solid fa-euro-sign me-2 fa-fw"></em>
            {{ __('firefly.currencies') }}
        </a>
    </div>
</li>
<li class="nav-item dropdown">
    <a class="nav-link" data-bs-toggle="dropdown" href="#">
        <i class="fa-solid fa-user"></i>
    </a>
    <div class="dropdown-menu dropdown-menu-lg dropdown-menu-end">
        <span class="dropdown-item dropdown-header">{{ auth()->user()->email }}</span>
        <div class="dropdown-divider"></div>
        <a href="{{ route('profile.index') }}" class="dropdown-item">
            <em class="fa-regular fa-user me-2"></em>
            {{ __('firefly.profile') }}
        </a>
        <div class="dropdown-divider"></div>
        <a href="{{ route('preferences.index') }}" class="dropdown-item">
            <em class="fa-solid fa-user-gear me-2"></em>
            {{ __('firefly.preferences') }}
        </a>
        <div class="dropdown-divider"></div>
        <a href="{{ route('administrations.index') }}" class="dropdown-item">
            <em class="fa-solid fa-money-bill-transfer me-2"></em>
            {{ __('firefly.administrations_index_menu') }}
        </a>
    </div>
</li>
<li class="nav-item dropdown">
    <a class="nav-link" data-bs-toggle="dropdown" href="#">
        <i class="fa-solid fa-plus-circle"></i>
    </a>
    <div class="dropdown-menu dropdown-menu-lg dropdown-menu-end">
        <!-- withdrawal, deposit, transfer -->
        <a href="{{ route('transactions.create', ['withdrawal']) }}" class="dropdown-item">
            <em class="fa-solid fa-arrow-left fa-fw me-2"></em>
            {{ __('firefly.create_new_withdrawal') }}
        </a>
        <a href="{{ route('transactions.create', ['deposit']) }}" class="dropdown-item">
            <em class="fa-solid fa-arrow-right fa-fw me-2"></em>
            {{ __('firefly.create_new_deposit') }}
        </a>
        <a href="{{ route('transactions.create', ['transfer']) }}" class="dropdown-item">
            <em class="fa-solid fa-arrows-rotate fa-fw me-2"></em>
            {{ __('firefly.create_new_transfer') }}
        </a>
        <div class="dropdown-divider"></div>

        <!-- asset, liability -->
        <a href="{{ route('accounts.create', ['asset']) }}" class="dropdown-item">
            <em class="fa-solid fa-money-bills fa-fw me-2"></em>
            {{ __('firefly.create_new_asset') }}
        </a>
        <a href="{{ route('accounts.create', ['liabilities']) }}" class="dropdown-item">
            <em class="fa-solid fa-landmark fa-fw me-2"></em>
            {{ __('firefly.create_new_liabilities') }}
        </a>
        <div class="dropdown-divider"></div>

        <!-- budget, category, piggy -->
        <a href="{{ route('budgets.create') }}" class="dropdown-item">
            <em class="fa-solid fa-pie-chart fa-fw me-2"></em>
            {{ __('firefly.create_new_budget') }}
        </a>
        <a href="{{ route('categories.create') }}" class="dropdown-item">
            <em class="fa-regular fa-bookmark fa-fw me-2"></em>
            {{ __('firefly.create_new_category') }}
        </a>
        <a href="{{ route('piggy-banks.create') }}" class="dropdown-item">
            <em class="fa-solid fa-piggy-bank fa-fw me-2"></em>
            {{ __('firefly.create_new_piggy_bank') }}
        </a>
        <div class="dropdown-divider"></div>

        <!-- contract, rule, recurring -->
        <a href="{{ route('subscriptions.create') }}" class="dropdown-item">
            <em class="fa-regular fa-calendar fa-fw me-2"></em>
            {{ __('firefly.create_new_subscription') }}
        </a>
        <a href="{{ route('rules.create') }}" class="dropdown-item">
            <em class="fa-solid fa-shuffle fa-fw me-2"></em>
            {{ __('firefly.create_new_rule') }}
        </a>
        <a href="{{ route('recurring.create') }}" class="dropdown-item">
            <em class="fa-solid fa-repeat fa-fw me-2"></em>
            {{ __('firefly.create_new_recurrence') }}
        </a>
        <a href="{{ route('webhooks.create') }}" class="dropdown-item">
            <em class="fa-solid fa-bolt-lightning fa-fw me-2"></em>
            {{ __('firefly.create_new_webhook') }}
        </a>
    </div>
</li>
