<li class="nav-item dropdown">
    <a class="nav-link" data-bs-toggle="dropdown" href="#">
        <i class="fa-solid fa-gears"></i>
    </a>
    <div class="dropdown-menu dropdown-menu-lg dropdown-menu-end">
        <a href="{{ route('admin.index') }}" class="dropdown-item">
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
        <a href="#" class="dropdown-item">
            <em class="fa-solid fa-money-bill-transfer me-2"></em>
            TODO {{ __('firefly.administrations_index_menu') }}
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
            <em class="fa-regular fa-plus me-2"></em>
            {{ __('firefly.create_new_transaction') }}
        </a>
        <a href="{{ route('transactions.create', ['withdrawal']) }}" class="dropdown-item">
            <em class="fa-regular fa-plus me-2"></em>
            TODO {{ __('firefly.create_new_transaction') }}
        </a>
        <a href="{{ route('transactions.create', ['withdrawal']) }}" class="dropdown-item">
            <em class="fa-regular fa-plus me-2"></em>
            TODO {{ __('firefly.create_new_transaction') }}
        </a>
        <div class="dropdown-divider"></div>

        <!-- asset, liability -->
        <a href="{{ route('transactions.create', ['withdrawal']) }}" class="dropdown-item">
            <em class="fa-regular fa-plus me-2"></em>
            TODO {{ __('firefly.create_new_transaction') }}
        </a>
        <a href="{{ route('transactions.create', ['withdrawal']) }}" class="dropdown-item">
            <em class="fa-regular fa-plus me-2"></em>
            TODO {{ __('firefly.create_new_transaction') }}
        </a>
        <div class="dropdown-divider"></div>

        <!-- budget, category, piggy -->
        <a href="{{ route('transactions.create', ['withdrawal']) }}" class="dropdown-item">
            <em class="fa-regular fa-plus me-2"></em>
            TODO {{ __('firefly.create_new_transaction') }}
        </a>
        <a href="{{ route('transactions.create', ['withdrawal']) }}" class="dropdown-item">
            <em class="fa-regular fa-plus me-2"></em>
            TODO {{ __('firefly.create_new_transaction') }}
        </a>
        <a href="{{ route('transactions.create', ['withdrawal']) }}" class="dropdown-item">
            <em class="fa-regular fa-plus me-2"></em>
            TODO {{ __('firefly.create_new_transaction') }}
        </a>
        <div class="dropdown-divider"></div>

        <!-- contract, rule, recurring -->
        <a href="{{ route('transactions.create', ['withdrawal']) }}" class="dropdown-item">
            <em class="fa-regular fa-plus me-2"></em>
            TODO {{ __('firefly.create_new_transaction') }}
        </a>
        <a href="{{ route('transactions.create', ['withdrawal']) }}" class="dropdown-item">
            <em class="fa-regular fa-plus me-2"></em>
            TODO {{ __('firefly.create_new_transaction') }}
        </a>
        <a href="{{ route('transactions.create', ['withdrawal']) }}" class="dropdown-item">
            <em class="fa-regular fa-plus me-2"></em>
            TODO {{ __('firefly.create_new_transaction') }}
        </a>
    </div>
</li>
