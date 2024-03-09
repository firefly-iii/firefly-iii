<!DOCTYPE html>
<html lang="en">
<head>
    <base href="{{ route('index') }}/" />
    <meta charset="utf-8">
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8">
    <title>{{ __('firefly.login_page_title')  }}</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="title" content="{{ __('firefly.login_page_title')  }}">

    <!-- copy of head.blade.php -->
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="robots" content="noindex, nofollow, noarchive, noodp, NoImageIndex, noydir">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="color-scheme" content="light dark">

    <script type="text/javascript" nonce="{{ $JS_NONCE }}">
        /*!
 * Color mode toggler for Bootstrap's docs (https://getbootstrap.com/)
 * Copyright 2011-2023 The Bootstrap Authors
 * Licensed under the Creative Commons Attribution 3.0 Unported License.
 */

        (() => {
            'use strict'
            // todo store just happens to store in localStorage but if not, this would break.
            const getStoredTheme = () => JSON.parse(localStorage.getItem('darkMode'))

            const getPreferredTheme = () => {
                const storedTheme = getStoredTheme()
                if (storedTheme) {
                    return storedTheme
                }

                return window.matchMedia('(prefers-color-scheme: dark)').matches ? 'dark' : 'light'
            }

            const setTheme = theme => {
                if (theme === 'browser' && window.matchMedia('(prefers-color-scheme: dark)').matches) {
                    document.documentElement.setAttribute('data-bs-theme', 'dark')
                    window.theme = 'dark';
                    return;
                }
                if (theme === 'browser' && window.matchMedia('(prefers-color-scheme: light)').matches) {
                    window.theme = 'light';
                    document.documentElement.setAttribute('data-bs-theme', 'light')
                    return;
                }
                document.documentElement.setAttribute('data-bs-theme', theme)
                window.theme = theme;
            }

            setTheme(getPreferredTheme())

            window.matchMedia('(prefers-color-scheme: dark)').addEventListener('change', () => {
                const storedTheme = getStoredTheme()
                if (storedTheme !== 'light' && storedTheme !== 'dark') {
                    setTheme(getPreferredTheme())
                }
            })
        })()
    </script>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link href="v2/css/fonts.css" rel="stylesheet">
    <link rel="stylesheet" href="v2/css/adminlte.css">
    <!-- session layout includes custom set of icons -->
    <link href="v2-local/fa/css/fontawesome.min.css" rel="stylesheet" />
    <link href="v2-local/fa/css/solid.min.css" rel="stylesheet" />

</head> <!--end::Head--> <!--begin::Body-->

<body class="login-page bg-body-secondary">
<div class="login-box">
    <div class="login-logo">
        <img src="images/logo-session.png" width="68" height="100" alt="Firefly III Logo" title="Firefly III" /><br>
        <a href='{{ route('index')  }}'><b>Firefly</b> III</a> </div>
    @yield('content')


</div> <!-- /.login-box --> <!--begin::Third Party Plugin(OverlayScrollbars)-->
<script src="v2/js/adminlte.js" nonce="{{ $JS_NONCE }}"></script>
@yield('scripts')
</body><!--end::Body-->
</html>
