<!DOCTYPE html>
<html lang="en">
<head>
    <base href="{{ route('index') }}/"/>
    <meta charset="utf-8">
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8">
    <title>Firefly III - Installation and update</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="title" content="Firefly III - Installation and update">

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
    @vite(['src/sass/app.scss'])

</head>
<body class="container bg-body-secondary">
<div class="row">
    <div class="col mt-3">
        <img src="images/logo-session.png" width="68" height="100" alt="Firefly III Logo" title="Firefly III"/><br>
    </div>
</div>
<div class="row">
    <div class="col">
        <h1><strong>Firefly</strong> III - <code>Installation and update</code></h1>
    </div>
</div>
<div class="row">
    <div class="col-xl-4 col-lg-4 col-md-6 col-sm-12">
        <div class="card">
            <div class="card-body">
                <p>The upgrade and installation is ongoing. Please track its progress through the box below.</p>
                <div class="row">
                    <div class="col-lg-12">
                        <div id="status-box" style="border:1px #ddd solid;padding:5px;">
                            <span class="fa fa-spin fa-spinner"></span> Waiting to start...
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@yield('scripts')
<script type="text/javascript" nonce="{{ $JS_NONCE }}">
    var token = '{{ csrf_token() }}';
    var index = 0;
    var runCommandUrl = '{{ route('installer.runCommand') }}';
    var homeUrl = '{{ route('flush') }}';
</script>
<script type="text/javascript" src="v1/js/ff/install/index.js" nonce="{{ $JS_NONCE }}"></script>
</body>
</html>
