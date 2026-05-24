<li class="nav-item dropdown">
    <a class="nav-link" data-bs-toggle="dropdown" href="#">
        <em class="bi bi-person"></em>
    </a>
    <div class="dropdown-menu dropdown-menu-lg dropdown-menu-end">
        <span class="dropdown-item dropdown-header">{{  Auth::user()->email  }}</span>
        <div class="dropdown-divider"></div>
        <a href="#" class="dropdown-item">
            <i class="bi bi-envelope me-2"></i> Profile
        </a>
        <div class="dropdown-divider"></div>
        <a href="#" class="dropdown-item">
            <i class="bi bi-people-fill me-2"></i> Preferences
        </a>
        <div class="dropdown-divider"></div>
        <a href="#" class="dropdown-item">
            <i class="bi bi-file-earmark-fill me-2"></i> Tokens
        </a>
        <div class="dropdown-divider"></div>
        <a href="#" class="dropdown-item">
            <i class="bi bi-file-earmark-fill me-2"></i> Financial administrations
        </a>
        <div class="dropdown-divider"></div>
        <a href="#" class="dropdown-item">
            <i class="bi bi-file-earmark-fill me-2"></i> System settings
        </a>
    </div>
</li>
