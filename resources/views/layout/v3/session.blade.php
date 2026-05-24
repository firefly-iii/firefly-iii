<!doctype html>
<html lang="{{ __('config.html_language') }}">
<!--begin::Head-->
<head>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="robots" content="noindex, nofollow, noarchive, noodp, NoImageIndex, noydir">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <!--
    If the base href URL begins with "http://" but you are sure it should start with "https://",
    please visit the following page: https://bit.ly/FF3-broken-base-href
    -->
    <base href="{{ route('index', null, true) }}/">
    <title>
        @if('' !== (string)$pageTitle)
        {{ $pageTitle }} »
        @endif
        @if('' !== (string)$subTitle && '' === (string) $pageTitle)
        {{ $subTitle }} »
        @endif
        @if('Firefly III' !== $title)
        {{ $title }} »
        @endif
        Firefly III
    </title>

    <!--begin::Accessibility Meta Tags-->
    <meta name="viewport" content="width=device-width, initial-scale=1.0, user-scalable=yes" />
    <meta name="color-scheme" content="light dark" />
    <meta name="theme-color" content="#007bff" media="(prefers-color-scheme: light)" />
    <meta name="theme-color" content="#1a1a1a" media="(prefers-color-scheme: dark)" />
    <!--end::Accessibility Meta Tags-->

    <!--begin::Accessibility Features-->
    <!-- Skip links will be dynamically added by accessibility.js -->
    @if('browser' === $darkMode)
     <meta name="color-scheme" content="light dark">
   @endif
    @if('dark' === $darkMode)
    <meta name="color-scheme" content="dark">
    @endif
    @if('light' === $darkMode)
    <meta name="color-scheme" content="light">
    @endif

    @vite(['sass/app.scss'])

    <x-layout.fav-icons />
    <!--end::Accessibility Features-->

</head>
<!--end::Head-->
<!--begin::Body-->
<body class="layout-fixed sidebar-expand-lg bg-body-tertiary">
<!--begin::App Wrapper-->
<div class="app-wrapper">
    <!--begin::Header-->
    <nav class="app-header navbar navbar-expand bg-body">
        <!--begin::Container-->
        <div class="container-fluid">
            <!--begin::Start Navbar Links-->
            <ul class="navbar-nav">
                <li class="nav-item">
                    <a class="nav-link" data-lte-toggle="sidebar" href="#" role="button">
                        <i class="bi bi-list"></i>
                    </a>
                </li>
                <!--
                <li class="nav-item d-none d-md-block">
                    <a href="./index.html" class="nav-link">
                        <i class="bi bi-grid-1x2 me-1" aria-hidden="true"></i>
                        Live preview
                    </a>
                </li>
                -->
                <!--
                <li class="nav-item d-none d-md-block">
                    <a href="./docs/introduction.html" class="nav-link">
                        <i class="bi bi-book me-1" aria-hidden="true"></i>
                        Documentation
                    </a>
                </li>
                -->
            </ul>
            <!--end::Start Navbar Links-->

            <!--begin::End Navbar Links-->
            <ul class="navbar-nav ms-auto">
                <!--begin::Navbar Search-->
                <li class="nav-item">
                    <a class="nav-link" data-widget="navbar-search" href="{{route('search.index')}}" role="button">
                        <i class="bi bi-search"></i>
                    </a>
                </li>
                <!--end::Navbar Search-->

                <!--begin::Messages Dropdown Menu-->
                {{--
                <li class="nav-item dropdown">
                    <a class="nav-link" data-bs-toggle="dropdown" href="#">
                        <i class="bi bi-chat-text"></i>
                        <span class="navbar-badge badge text-bg-danger">3</span>
                    </a>
                    <div class="dropdown-menu dropdown-menu-lg dropdown-menu-end">
                        <a href="#" class="dropdown-item">
                            <!--begin::Message-->
                            <div class="d-flex">
                                <div class="flex-shrink-0">
                                    <img
                                        src="./assets/img/user1-128x128.jpg"
                                        alt="User Avatar"
                                        class="img-size-50 rounded-circle me-3"
                                    />
                                </div>
                                <div class="flex-grow-1">
                                    <h3 class="dropdown-item-title">
                                        Brad Diesel
                                        <span class="float-end fs-7 text-danger"
                                        ><i class="bi bi-star-fill"></i
                                            ></span>
                                    </h3>
                                    <p class="fs-7">Call me whenever you can...</p>
                                    <p class="fs-7 text-secondary">
                                        <i class="bi bi-clock-fill me-1"></i> 4 Hours Ago
                                    </p>
                                </div>
                            </div>
                            <!--end::Message-->
                        </a>
                        <div class="dropdown-divider"></div>
                        <a href="#" class="dropdown-item">
                            <!--begin::Message-->
                            <div class="d-flex">
                                <div class="flex-shrink-0">
                                    <img
                                        src="./assets/img/user8-128x128.jpg"
                                        alt="User Avatar"
                                        class="img-size-50 rounded-circle me-3"
                                    />
                                </div>
                                <div class="flex-grow-1">
                                    <h3 class="dropdown-item-title">
                                        John Pierce
                                        <span class="float-end fs-7 text-secondary">
                          <i class="bi bi-star-fill"></i>
                        </span>
                                    </h3>
                                    <p class="fs-7">I got your message bro</p>
                                    <p class="fs-7 text-secondary">
                                        <i class="bi bi-clock-fill me-1"></i> 4 Hours Ago
                                    </p>
                                </div>
                            </div>
                            <!--end::Message-->
                        </a>
                        <div class="dropdown-divider"></div>
                        <a href="#" class="dropdown-item">
                            <!--begin::Message-->
                            <div class="d-flex">
                                <div class="flex-shrink-0">
                                    <img
                                        src="./assets/img/user3-128x128.jpg"
                                        alt="User Avatar"
                                        class="img-size-50 rounded-circle me-3"
                                    />
                                </div>
                                <div class="flex-grow-1">
                                    <h3 class="dropdown-item-title">
                                        Nora Silvester
                                        <span class="float-end fs-7 text-warning">
                          <i class="bi bi-star-fill"></i>
                        </span>
                                    </h3>
                                    <p class="fs-7">The subject goes here</p>
                                    <p class="fs-7 text-secondary">
                                        <i class="bi bi-clock-fill me-1"></i> 4 Hours Ago
                                    </p>
                                </div>
                            </div>
                            <!--end::Message-->
                        </a>
                        <div class="dropdown-divider"></div>
                        <a href="#" class="dropdown-item dropdown-footer">See All Messages</a>
                    </div>
                </li>
                --}}
                <!--end::Messages Dropdown Menu-->

                <!--begin::Notifications Dropdown Menu-->
                {{--
                <li class="nav-item dropdown">
                    <a class="nav-link" data-bs-toggle="dropdown" href="#">
                        <i class="bi bi-bell-fill"></i>
                        <span class="navbar-badge badge text-bg-warning">15</span>
                    </a>
                    <div class="dropdown-menu dropdown-menu-lg dropdown-menu-end">
                        <span class="dropdown-item dropdown-header">15 Notifications</span>
                        <div class="dropdown-divider"></div>
                        <a href="#" class="dropdown-item">
                            <i class="bi bi-envelope me-2"></i> 4 new messages
                            <span class="float-end text-secondary fs-7">3 mins</span>
                        </a>
                        <div class="dropdown-divider"></div>
                        <a href="#" class="dropdown-item">
                            <i class="bi bi-people-fill me-2"></i> 8 friend requests
                            <span class="float-end text-secondary fs-7">12 hours</span>
                        </a>
                        <div class="dropdown-divider"></div>
                        <a href="#" class="dropdown-item">
                            <i class="bi bi-file-earmark-fill me-2"></i> 3 new reports
                            <span class="float-end text-secondary fs-7">2 days</span>
                        </a>
                        <div class="dropdown-divider"></div>
                        <a href="#" class="dropdown-item dropdown-footer"> See All Notifications </a>
                    </div>
                </li>
                --}}
                <!--end::Notifications Dropdown Menu-->

                <!--begin::Fullscreen Toggle-->
                <li class="nav-item">
                    <a class="nav-link" href="#" data-lte-toggle="fullscreen">
                        <i data-lte-icon="maximize" class="bi bi-arrows-fullscreen"></i>
                        <i data-lte-icon="minimize" class="bi bi-fullscreen-exit d-none"></i>
                    </a>
                </li>
                <!--end::Fullscreen Toggle-->

                <!--begin::Color Mode Toggle (#6010)-->
                <li class="nav-item dropdown">
                    <a
                        class="nav-link"
                        href="#"
                        id="bd-theme"
                        aria-label="Toggle color scheme"
                        data-bs-toggle="dropdown"
                        aria-expanded="false"
                    >
                        <i class="bi bi-sun-fill" data-lte-theme-icon="light"></i>
                        <i class="bi bi-moon-fill d-none" data-lte-theme-icon="dark"></i>
                        <i class="bi bi-circle-half d-none" data-lte-theme-icon="auto"></i>
                    </a>
                    <ul
                        class="dropdown-menu dropdown-menu-end"
                        aria-labelledby="bd-theme"
                        style="--bs-dropdown-min-width: 8rem"
                    >
                        <li>
                            <button
                                type="button"
                                class="dropdown-item d-flex align-items-center"
                                data-bs-theme-value="light"
                                aria-pressed="false"
                            >
                                <i class="bi bi-sun-fill me-2"></i>
                                Light
                                <i class="bi bi-check-lg ms-auto d-none"></i>
                            </button>
                        </li>
                        <li>
                            <button
                                type="button"
                                class="dropdown-item d-flex align-items-center"
                                data-bs-theme-value="dark"
                                aria-pressed="false"
                            >
                                <i class="bi bi-moon-fill me-2"></i>
                                Dark
                                <i class="bi bi-check-lg ms-auto d-none"></i>
                            </button>
                        </li>
                        <li>
                            <button
                                type="button"
                                class="dropdown-item d-flex align-items-center active"
                                data-bs-theme-value="auto"
                                aria-pressed="true"
                            >
                                <i class="bi bi-circle-half me-2"></i>
                                Auto
                                <i class="bi bi-check-lg ms-auto d-none"></i>
                            </button>
                        </li>
                    </ul>
                </li>
                <!--end::Color Mode Toggle-->

                <!--begin::User Menu Dropdown-->
                {{--
                <li class="nav-item dropdown user-menu">
                    <a href="#" class="nav-link dropdown-toggle" data-bs-toggle="dropdown">
                        <span class="d-none d-md-inline"> {{ Auth::user()->email }}</span>
                    </a>
                    <ul class="dropdown-menu dropdown-menu-lg dropdown-menu-end">

                        <!--begin::User Image-->

                        <!--end::User Image-->
                        <!--begin::Menu Body-->
                        <li class="user-body">
                            <!--begin::Row-->
                            <div class="row">
                                <div>
                                    <em class="nav-icon bi bi-sliders"></em> <a href="#">Friends</a>
                                </div>
                            </div>
                            <div class="row">
                                <div>
                                    <em class="nav-icon bi bi-sliders"></em> <a href="#">Friends</a>
                                </div>
                            </div>
                            <!--end::Row-->
                        </li>
                        <!--end::Menu Body-->
                        <!--begin::Menu Footer-->
                        <li class="user-footer">
                            <a href="#" class="btn btn-outline-danger float-end">Sign out</a>
                        </li>
                        <!--end::Menu Footer-->
                    </ul>
                </li>
                --}}
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
                            <i class="bi bi-file-earmark-fill me-2"></i> Financial administrations
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
                <!--end::User Menu Dropdown-->
            </ul>
            <!--end::End Navbar Links-->
        </div>
        <!--end::Container-->
    </nav>
    <!--end::Header-->
    <!--begin::Sidebar-->
    <aside class="app-sidebar bg-body-secondary shadow" data-bs-theme="dark">
        <!--begin::Sidebar Brand-->
        <div class="sidebar-brand">
            <!--begin::Brand Link-->
            <a href="./index.html" class="brand-link">
                <!--begin::Brand Image-->
                <img
                    src="./images/logo-session.png"
                    alt="Firefly III"
                    class="brand-image opacity-75 shadow"
                />
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
                <x-layout.sidebar />
                <!--end::Sidebar Menu-->

                <!-- Docs CTA (bottom of sidebar) -->
                <div class="p-3 mt-3 border-top border-secondary border-opacity-25">
                    <a
                        href="https://docs.firefly-iii.org/"
                        class="btn btn-sm btn-outline-light w-100 d-flex align-items-center justify-content-center gap-2"
                    >
                        <i class="bi bi-book" aria-hidden="true"></i>
                        {{ trans('firefly.view_documentation') }}
                    </a>
                </div>
            </nav>
        </div>
        <!--end::Sidebar Wrapper-->
    </aside>
    <!--end::Sidebar-->
    <!--begin::App Main-->
    <main class="app-main">
        <!--begin::App Content Header-->
        <div class="app-content-header">
            <!--begin::Container-->
            <div class="container-fluid">
                <!--begin::Row-->
                <div class="row">
                    <div class="col-sm-6">
                        <h3 class="mb-0">Header</h3>
                    </div>
                    <div class="col-sm-6">
                        <ol class="breadcrumb float-sm-end">
                            <li class="breadcrumb-item"><a href="#">Home</a></li>
                            <li class="breadcrumb-item active" aria-current="page">Dashboard</li>
                        </ol>
                    </div>
                </div>
                <!--end::Row-->
            </div>
            <!--end::Container-->
        </div>
        <!--end::App Content Header-->
        <!--begin::App Content-->
        <div class="app-content">
            <!--begin::Container-->
            <div class="container-fluid">
                @yield('content')

                <!--begin::Row-->
                <!--end::Row-->
                <!--begin::Row-->
                <!-- /.row (main row) -->
            </div>
            <!--end::Container-->
        </div>
        <!--end::App Content-->
    </main>
    <!--end::App Main-->
    <!--begin::Footer-->
    <footer class="app-footer">
        <!--begin::To the end-->
        <div class="float-end d-none d-sm-inline">Anything you want</div>
        <!--end::To the end-->
        <!--begin::Copyright-->
        <strong>
            Bla bla bla
            <a href="https://adminlte.io" class="text-decoration-none">AdminLTE.io</a>.
        </strong>
        All rights reserved.
        <!--end::Copyright-->
    </footer>
    <!--end::Footer-->
</div>
<!--end::App Wrapper-->
<!--begin::Script-->
<!--begin::Third Party Plugin(OverlayScrollbars)-->
<!--end::Third Party Plugin(OverlayScrollbars)--><!--begin::Required Plugin(popperjs for Bootstrap 5)-->
<!--end::Required Plugin(popperjs for Bootstrap 5)--><!--begin::Required Plugin(Bootstrap 5)-->
<!--end::Required Plugin(Bootstrap 5)--><!--begin::Required Plugin(AdminLTE)-->
<!--end::Required Plugin(AdminLTE)-->
<!--begin::OverlayScrollbars Configure-->

<!--end::OverlayScrollbars Configure-->

@yield('scripts')

<!--begin::Color Mode Toggle (#6010)-->
<script>
    (() => {
        'use strict';

        const STORAGE_KEY = 'lte-theme';

        const getStoredTheme = () => localStorage.getItem(STORAGE_KEY);
        const setStoredTheme = (theme) => localStorage.setItem(STORAGE_KEY, theme);

        const prefersDark = () => globalThis.matchMedia('(prefers-color-scheme: dark)').matches;

        const getPreferredTheme = () => {
            const stored = getStoredTheme();
            if (stored) return stored;
            return prefersDark() ? 'dark' : 'light';
        };

        const setTheme = (theme) => {
            const resolved = theme === 'auto' ? (prefersDark() ? 'dark' : 'light') : theme;
            document.documentElement.setAttribute('data-bs-theme', resolved);
        };

        setTheme(getPreferredTheme());

        const showActiveTheme = (theme) => {
            // Highlight the active dropdown option
            document.querySelectorAll('[data-bs-theme-value]').forEach((el) => {
                el.classList.remove('active');
                el.setAttribute('aria-pressed', 'false');
                const check = el.querySelector('.bi-check-lg');
                if (check) check.classList.add('d-none');
            });
            const active = document.querySelector(`[data-bs-theme-value="${theme}"]`);
            if (active) {
                active.classList.add('active');
                active.setAttribute('aria-pressed', 'true');
                const check = active.querySelector('.bi-check-lg');
                if (check) check.classList.remove('d-none');
            }
            // Sync the topbar trigger icon
            document.querySelectorAll('[data-lte-theme-icon]').forEach((icon) => {
                icon.classList.toggle('d-none', icon.dataset.lteThemeIcon !== theme);
            });
        };

        globalThis.matchMedia('(prefers-color-scheme: dark)').addEventListener('change', () => {
            const stored = getStoredTheme();
            if (!stored || stored === 'auto') setTheme(getPreferredTheme());
        });

        document.addEventListener('DOMContentLoaded', () => {
            showActiveTheme(getPreferredTheme());
            document.querySelectorAll('[data-bs-theme-value]').forEach((toggle) => {
                toggle.addEventListener('click', () => {
                    const theme = toggle.getAttribute('data-bs-theme-value');
                    setStoredTheme(theme);
                    setTheme(theme);
                    showActiveTheme(theme);
                });
            });
        });
    })();
</script>
<!--end::Color Mode Toggle-->

<!-- OPTIONAL SCRIPTS -->

<!-- sortablejs -->

<!-- sortablejs -->

<!-- apexcharts -->

<!-- ChartJS -->

<!-- jsvectormap -->
<!-- jsvectormap -->
<!--end::Script-->
</body>
<!--end::Body-->
</html>
