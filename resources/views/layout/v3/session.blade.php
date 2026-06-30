<!doctype html>
<html lang="{{ __('config.html_language') }}">
<head>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="robots" content="noindex, nofollow, noarchive, noodp, NoImageIndex, noydir">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="mobile-web-app-capable" content="yes">
    <!--
    If the base href URL begins with "http://" but you are sure it should start with "https://",
    please visit the following page: https://bit.ly/FF3-broken-base-href
    -->
    <base href="{{ route('index', null, true) }}/">
    <title>
        @if('' !== (string)($pageTitle ?? ''))
            {{ $pageTitle }} »
        @endif
        @if($subTitle ?? false && '' !== (string)$subTitle && '' === (string) ($pageTitle ?? ''))
            {{ $subTitle }} »
        @endif
        @if('Firefly III' !== $title)
            {{ $title }} »
        @endif
        Firefly III
    </title>
    <!--begin::Theme Init (prevents flash of incorrect theme on load, #6043)-->
    <script nonce="{{ $JS_NONCE }}">
        (() => {
            'use strict';
            let stored = '{{ $darkMode }}';
            const prefersDark = globalThis.matchMedia('(prefers-color-scheme: dark)').matches;
            // Mirror the resolution in _scripts.astro: explicit "dark"/"light" win,
            // otherwise ("auto" or unset) fall back to the OS preference.
            let resolved = 'light';
            if (stored === 'dark' || stored === 'light') {
                resolved = stored;
            } else if (prefersDark) {
                resolved = 'dark';
            }
            document.documentElement.setAttribute('data-bs-theme', resolved);
            document.documentElement.style.colorScheme = resolved;
        })();
    </script>
    <!--end::Theme Init-->

    <!--begin::Accessibility Meta Tags-->
    <meta name="viewport" content="width=device-width, initial-scale=1.0, user-scalable=yes"/>
    <meta name="theme-color" content="#007bff" media="(prefers-color-scheme: light)"/>
    <meta name="theme-color" content="#1a1a1a" media="(prefers-color-scheme: dark)"/>
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

    <x-layout.fav-icons/>
    <!--end::Accessibility Features-->


</head>
<!--end::Head-->
<!--begin::Body-->
<body class="layout-fixed sidebar-mini sidebar-expand-lg bg-body-tertiary">
{{-- this entry is in the header so it's loaded early --}}
<script type="text/javascript" nonce="{{ $JS_NONCE }}">
    var forceDemoOff = false;
    if ('true' === localStorage.getItem('ff3_sidebar_collapsed')) {
        document.body.classList.add('sidebar-collapse');
    }
</script>
<!-- tussen -->

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
                <li class="nav-item">
                    <span class="nav-link">
                        Size:
                        <span class="d-inline d-sm-none">xs</span>
                        <span class="d-none d-sm-inline d-md-none">sm</span>
                        <span class="d-none d-md-inline d-lg-none">md</span>
                        <span class="d-none d-lg-inline d-xl-none">lg</span>
                        <span class="d-none d-xl-inline d-xxl-none">xl</span>
                        <span class="d-none d-xxl-inline">xxl</span>
                    </span>
                </li>

                <!-- start: date range selector -->
                <x-layout.range/>
                <!-- end: date range selector -->


                <!-- anonymous -->

                <li class="nav-item">
                    <a class="nav-link" href="#" id="anonymous">
                        @if($anonymous)
                            <span class="text-danger bi bi-eye-slash"></span>
                        @endif
                        @if(!$anonymous)
                            <span class="text-success bi bi-eye"></span>
                        @endif
                    </a>
                </li>
                <!-- end anonymous -->
                <!-- help button -->
                <li class="nav-item hidden-sm hidden-xs">
                    <a href="#" class="nav-link" id="help" data-extra="{{ $objectType ?? '' }}"
                       data-route="{{ $original_route_name }}" data-bs-toggle="modal" data-bs-target="#helpModal">
                        <em class="bi bi-question-circle"></em>
                    </a>
                </li>
                <!-- end help button -->

                <!-- hierboven -->


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
                        <i class="bi bi-circle-half d-none" data-lte-theme-icon="browser"></i>
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
                                {{ __('firefly.dark_mode_option_light') }}
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
                                {{ __('firefly.dark_mode_option_dark') }}
                                <i class="bi bi-check-lg ms-auto d-none"></i>
                            </button>
                        </li>
                        <li>
                            <button
                                type="button"
                                class="dropdown-item d-flex align-items-center active"
                                data-bs-theme-value="browser"
                                aria-pressed="true"
                            >
                                <i class="bi bi-circle-half me-2"></i>
                                {{ __('firefly.dark_mode_option_browser') }}
                                <i class="bi bi-check-lg ms-auto d-none"></i>
                            </button>
                        </li>
                    </ul>
                </li>
                <!--end::Color Mode Toggle-->
                <x-layout.create-menu/>
                <x-layout.user-menu/>

                <!--end::User Menu Dropdown-->
            </ul>
            <!--end::End Navbar Links-->
        </div>
        <!--end::Container-->
    </nav>
    <!--end::Header-->
    <!--begin::Sidebar-->
    <aside class="app-sidebar bg-body-secondary shadow">
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

                <!-- einde tussen -->

        <!--end::Sidebar Brand-->
        <!--begin::Sidebar Wrapper-->
        <div class="sidebar-wrapper">
            <nav class="mt-2">
                <!--begin::Sidebar Menu-->
                <x-layout.sidebar/>
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
                            {{ $pageTitle ?? $title ?? '(no title)' }}

                            @if($subTitle ?? false)
                                <small class="text-xs text-muted">@if(isset($subTitleIcon))
                                        <em class="bi {{ $subTitleIcon }}"></em>
                                    @endif{{$subTitle}}</small>
                            @endif
                        </h3>
                    </div>

                    <div class="col-sm-6">
                        @sectionMissing('breadcrumbs')
                            {{ Breadcrumbs::render() }}
                        @endif
                        @yield('breadcrumbs')

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
                @if($IS_DEMO_SITE)
                    <div class="row no-print">
                        <div class="col-lg-12">
                            <div class="alert alert-info" role="alert">
                                @includeFirst(['demo.' . Route::getCurrentRoute()->getName(), 'demo.no-demo-text'])
                            </div>
                        </div>
                    </div>
                @endif
                <x-layout.flash
                    :invalid-monetary-locale="$invalidMonetaryLocale ?? false"
                    :upgrade-security-level="$upgrade_security_level ?? ''"
                    :upgrade-security-message="$upgrade_security_message ?? ''"
                />

                @yield('content')
        </div>
        </div>
    </main>
    <footer class="app-footer">
        <div class="float-end d-none d-sm-inline">
            <a href="{{route('debug')}}">v{{ $FF_VERSION }}</a>
        </div>
        <span>
            <a href="https://www.firefly-iii.org/" target="_blank" title="Firefly III">Firefly III</a> &copy; James Cole, <a
                href="https://www.gnu.org/licenses/agpl-3.0.html" title="AGPL-3.0-or-later.">AGPL-3.0-or-later</a>.
            @if($FF_IS_ALPHA)<small class="text-danger hidden-xs"><br>{{ __('firefly.is_alpha_warning') }}</small>@endif
            @if($FF_IS_BETA)<small class="text-warning hidden-xs"><br>{{ __('firefly.is_beta_warning') }}</small>@endif
        </span>
    </footer>
</div>

{{-- Moment JS  --}}
<script src="v1/js/lib/moment.min.js?v={{ $FF_BUILD_TIME }}" type="text/javascript" nonce="{{ $JS_NONCE }}"></script>
<script src="v1/js/lib/moment/{{ str_replace('-','_', $language) }}.js?v={{ $FF_BUILD_TIME }}" type="text/javascript"
        nonce="{{ $JS_NONCE }}"></script>

{{-- All kinds of variables. --}}
<script
    src="{{ route('javascript.variables') }}?ext=.js&amp;v={{ $FF_VERSION }}@if(isset($account) && is_object($account))&amp;account={{ $account->id }}@elseif(isset($account) && is_array($account))&amp;account={{ $account['id'] ?? '' }}@endif"
    type="text/javascript" nonce="{{ $JS_NONCE }}"></script>

{{-- Base script: jquery and bootstrap --}}
<script src="v1/js/app.js?v={{ $FF_BUILD_TIME }}" type="text/javascript" nonce="{{ $JS_NONCE }}"></script>

{{-- introduction --}}
@if(!$shownDemo)
    <script type="text/javascript" nonce="{{ $JS_NONCE }}">
        var showTour = true;
        var routeForTour = "{{ $current_route_name }}";
        var routeStepsUrl = "{{ route('json.intro', [$current_route_name, $objectType ?? '']) }}";
        var routeForFinishedTour = "{{ route('json.intro.finished', [$current_route_name, $objectType ?? '']) }}";
    </script>
@endif
@if($shownDemo)
    <script type="text/javascript" nonce="{{ $JS_NONCE }}">
        var showTour = false;
    </script>
@endif

{{-- date range picker, current template, etc. --}}

<script type="text/javascript" src="v1/js/lib/accounting.min.js?v={{ $FF_BUILD_TIME }}"
        nonce="{{ $JS_NONCE }}"></script>

{{--  Firefly III code --}}
<script type="text/javascript" src="v1/js/ff/firefly.js?v={{ $FF_BUILD_TIME }}" nonce="{{ $JS_NONCE }}"></script>
<script type="text/javascript" src="v1/js/ff/help.js?v={{ $FF_BUILD_TIME }}" nonce="{{ $JS_NONCE }}"></script>

@yield('scripts')

<script nonce="{{ $JS_NONCE }}">
    (() => {
        'use strict';
        const STORAGE_KEY = 'lte-theme';

        const setStoredTheme = (theme) => {
            localStorage.setItem(STORAGE_KEY, theme);
            window.axios.put('api/v1/preferences/darkMode', {data: theme});
        }

        const prefersDark = () => globalThis.matchMedia('(prefers-color-scheme: dark)').matches;

        const setTheme = (theme) => {
            const resolved = theme === 'browser' ? (prefersDark() ? 'dark' : 'light') : theme;
            document.documentElement.setAttribute('data-bs-theme', resolved);
        };

        setTheme('{{ $darkMode }}');

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
            const stored = '{{ $darkMode }}';
            if (!stored || stored === 'browser') setTheme('{{ $darkMode }}');
        });

        document.addEventListener('DOMContentLoaded', () => {
            showActiveTheme('{{ $darkMode }}');
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
<form id="logout-form" action="{{ route('logout') }}" method="POST" class="hidden">
    <input type="hidden" name="_token" value="{{ csrf_token() }}"/>
</form>

<div class="modal fade" tabindex="-1" role="dialog" id="customDateRangeModal" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <form action="{{ route('daterange') }}?redirect=true" method="POST" id="daterange-form">
            <input type="hidden" name="start" value="" id="customStart"/>
            <input type="hidden" name="end" value="" id="customEnd"/>
            <div class="modal-content">
                <div class="modal-header">
                    <h4 class="modal-title">{{ __('firefly.customRange') }}</h4>
                </div>
                <div class="modal-body">
                    <style>
                        calendar-range {
                            svg {
                                height: 16px;
                                width: 16px;
                                fill: none;
                                stroke: currentColor;
                                stroke-width: 1.5;
                            }

                            path {
                                stroke-linecap: round;
                                stroke-linejoin: round;
                            }

                            &::part(months) {
                                display: flex;
                                gap: 1.5em;
                                flex-wrap: wrap;
                                justify-content: center;
                            }

                            &::part(button) {
                                border: 1px solid #adb5bd;
                                border-radius: 3px;
                                width: 26px;
                                height: 26px;
                            }

                            &::part(button):focus-visible {
                                outline: 2px solid #1E6581;
                            }
                        }

                        calendar-month {
                            --color-accent: #1E6581;
                            --color-text-on-accent: #ffffff;

                            &::part(button) {
                                border-radius: 3px;
                            }

                            &::part(range-inner) {
                                border-radius: 0;
                                background-color: #2885AA;
                            }

                            &::part(range-start) {
                                border-start-end-radius: 0;
                                border-end-end-radius: 0;
                            }

                            &::part(range-end) {
                                border-start-start-radius: 0;
                                border-end-start-radius: 0;
                            }

                            &::part(range-start range-end) {
                                border-radius: 3px;
                            }
                        }
                    </style>
                    <div class="row">
                        <div class="col" style="width:100%;display:flex;justify-content:center;">
                            <calendar-range months="2" x-on:change="updateDates">
                                <svg
                                    aria-label="Previous"
                                    slot="previous"
                                    xmlns="http://www.w3.org/2000/svg"
                                    viewBox="0 0 24 24"
                                >
                                    <path d="M15.75 19.5 8.25 12l7.5-7.5"></path>
                                </svg>
                                <svg
                                    aria-label="Next"
                                    slot="next"
                                    xmlns="http://www.w3.org/2000/svg"
                                    viewBox="0 0 24 24"
                                >
                                    <path d="m8.25 4.5 7.5 7.5-7.5 7.5"></path>
                                </svg>
                                <calendar-month></calendar-month>
                                <calendar-month offset="1"></calendar-month>
                            </calendar-range>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">{{ __('firefly.close') }}</button>
                    <button type="submit" class="btn btn-primary">{{ trans('firefly.submit') }}</button>
                </div>
            </div>
        </form>
    </div>
</div>

<div class="modal fade" id="defaultModal" tabindex="-1" role="dialog">
</div>

<div class="modal fade" tabindex="-1" role="dialog" id="helpModal">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h4 class="modal-title" id="helpTitle">&nbsp;</h4>
            </div>
            <div class="modal-body" id="helpBody">&nbsp;</div>
            <div class="modal-footer">
                <small class="pull-left">
                    {!! __('firefly.need_more_help') !!}
                </small>
                <br/>
                <small class="pull-left">
                    {!!  trans('firefly.reenable_intro_text')  !!}
                </small>
                <button type="button" class="btn btn-primary" data-bs-dismiss="modal">{{ trans('firefly.close') }}</button>
            </div>
        </div>
    </div>
</div>
<x-layout.tracking />

@if('' !== config('firefly.tracker_site_id') && '' !== config('firefly.tracker_url'))
    <!-- This tracker tag is only here because this instance of Firefly III was purposefully configured to include it -->
    <!-- Your own installation will NOT include it, unless you explicitly configure it to have it. -->
    <script type="text/javascript" nonce="{{ $JS_NONCE }}">
        var _paq = window._paq || [];
        _paq.push(['trackPageView']);
        _paq.push(['enableLinkTracking']);
        (function () {
            var u = "//{{ config('firefly.tracker_url') }}/";
            _paq.push(['setTrackerUrl', u + 'matomo.php']);
            _paq.push(['setSiteId', '{{ config('firefly.tracker_site_id') }}']);
            var d = document, g = d.createElement('script'), s = d.getElementsByTagName('script')[0];
            g.type = 'text/javascript';
            g.async = true;
            g.defer = true;
            g.src = u + 'matomo.js';
            s.parentNode.insertBefore(g, s);
        })();
    </script>
    <noscript><p><img
                src="//{{ config('firefly.tracker_url') }}/matomo.php?idsite={{ config('firefly.tracker_site_id') }}&amp;rec=1"
                class="no-border" alt=""/></p></noscript>
@endif


</body>
<!--end::Body-->
</html>
