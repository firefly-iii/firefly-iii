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
<body class="layout-fixed sidebar-mini  sidebar-expand-lg bg-body-tertiary">
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
            </ul>
            <!--begin::End Navbar Links-->
            <ul class="navbar-nav ms-auto">
                <!--begin::Navbar Search-->
                <li class="nav-item">
                    <a class="nav-link" data-widget="navbar-search" href="{{route('search.index')}}" role="button">
                        <em class="bi bi-search"></em>
                    </a>
                </li>
                <!--end::Navbar Search-->

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
                <x-layout.create-menu />
                <x-layout.user-menu />

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
            <a href="{{ route('index') }}" class="brand-link">
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
                {{--
                <div class="p-3 mt-3 border-top border-secondary border-opacity-25">
                    <a
                        href="https://docs.firefly-iii.org/"
                        class="btn btn-sm btn-outline-light w-100 d-flex align-items-center justify-content-center gap-2"
                    >
                        <i class="bi bi-book" aria-hidden="true"></i>
                        {{ trans('firefly.view_documentation') }}
                    </a>
                </div>
                --}}
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
                        <h3 class="mb-0">
                            <em class="bi {{ $mainTitleIcon }}"></em>
                            {{ $pageTitle }}

                            <small class="text-xs text-muted">@if(isset($subTitleIcon))<em class="bi {{ $subTitleIcon }}"></em>X @endif{{$subTitle}}</small>
                        </h3>
                    </div>

                    <div class="col-sm-6">
                        {{ Breadcrumbs::render() }}
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
        <div class="float-end d-none d-sm-inline">
            <a href="{{route('debug')}}">v{{ $FF_VERSION }}</a>
        </div>
        <!--end::To the end-->
        <!--begin::Copyright-->
        <span>
            <a href="https://www.firefly-iii.org/" target="_blank" title="Firefly III">Firefly III</a> &copy; James Cole, <a href="https://www.gnu.org/licenses/agpl-3.0.html" title="AGPL-3.0-or-later.">AGPL-3.0-or-later</a>.
        </span>
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

{{-- Moment JS  --}}
<script src="v1/js/lib/moment.min.js?v={{ $FF_BUILD_TIME }}" type="text/javascript" nonce="{{ $JS_NONCE }}"></script>
<script src="v1/js/lib/moment/{{ str_replace($language,'-','_') }}.js?v={{ $FF_BUILD_TIME }}" type="text/javascript" nonce="{{ $JS_NONCE }}"></script>

{{-- All kinds of variables. --}}
<script src="{{ route('javascript.variables') }}?ext=.js&amp;v={{ $FF_VERSION }}@if(isset($account))&amp;account={{ $account->id }}@endif" type="text/javascript" nonce="{{ $JS_NONCE }}"></script>

{{-- Base script: jquery and bootstrap --}}
<script src="v1/js/app.js?v={{ $FF_BUILD_TIME }}" type="text/javascript" nonce="{{ $JS_NONCE }}"></script>


{{-- date range picker, current template, etc. --}}
<script src="v1/js/lib/daterangepicker.js?v={{ $FF_BUILD_TIME }}" type="text/javascript" nonce="{{ $JS_NONCE }}"></script>
<script src="v1/lib/adminlte/js/adminlte.min.js?v={{ $FF_BUILD_TIME }}" type="text/javascript" nonce="{{ $JS_NONCE }}"></script>
<script type="text/javascript" src="v1/js/lib/accounting.min.js?v={{ $FF_BUILD_TIME }}" nonce="{{ $JS_NONCE }}"></script>

{{--  Firefly III code --}}
<script type="text/javascript" src="v1/js/ff/firefly.js?v={{ $FF_BUILD_TIME }}" nonce="{{ $JS_NONCE }}"></script>
<script type="text/javascript" src="v1/js/ff/help.js?v={{ $FF_BUILD_TIME }}" nonce="{{ $JS_NONCE }}"></script>

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
<form id="logout-form" action="{{ route('logout') }}" method="POST" class="hidden">
    <input type="hidden" name="_token" value="{{ csrf_token() }}"/>
</form>
</body>
<!--end::Body-->
</html>
