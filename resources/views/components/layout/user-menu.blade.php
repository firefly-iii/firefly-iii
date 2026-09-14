<li class="nav-item dropdown">
    <a class="nav-link" data-bs-toggle="dropdown" href="#">
        <em class="bi bi-person"></em>
    </a>
    <div class="dropdown-menu dropdown-menu-lg dropdown-menu-end">
        <span class="dropdown-item dropdown-header">{{  Auth::user()->email  }}</span>
        <div class="dropdown-divider"></div>
        <a href="{{ route('profile.index') }}" class="dropdown-item">
            <em class="bi bi-person me-2"></em> {{ __('firefly.profile') }}
        </a>
        <div class="dropdown-divider"></div>
        <a href="{{ route('preferences.index') }}" class="dropdown-item">
            <em class="bi bi-gear-wide-connected me-2"></em> {{ __('firefly.preferences') }}
        </a>
        <div class="dropdown-divider"></div>
        <a href="{{ route('profile.oauth.index') }}" class="dropdown-item">
            <em class="bi bi-shield-lock me-2"></em> {{ __('firefly.oauth_tokens') }}
        </a>
        <div class="dropdown-divider"></div>
        <a href="{{ route('administrations.index') }}" class="dropdown-item">
            <em class="bi bi-journals me-2"></em> {{ __('firefly.administrations_index_menu') }}
        </a>
        <div class="dropdown-divider"></div>
        <a href="{{ route('settings.index') }}" class="dropdown-item">
            <em class="bi bi-cpu me-2"></em> {{ __('firefly.system_settings') }}
        </a>
    </div>
</li>
