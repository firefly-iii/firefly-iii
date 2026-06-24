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
    <title>{{ __('firefly.login_page_title')  }}</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0, user-scalable=yes"/>
    <meta name="theme-color" content="#007bff" media="(prefers-color-scheme: light)"/>
    <meta name="theme-color" content="#1a1a1a" media="(prefers-color-scheme: dark)"/>
    <meta name="color-scheme" content="light dark">
    @vite(['sass/app.scss'])
    <x-layout.fav-icons/>
    <script nonce="{{ $JS_NONCE ?? '' }}">
        (() => {
            'use strict'
            document.documentElement.setAttribute('data-bs-theme', (window.matchMedia('(prefers-color-scheme: dark)').matches ? 'dark' : 'light'))
        })()
    </script>
</head>
<body class="bg-body-tertiary">
<main class="align-items-center min-vh-100 py-5">
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-md-12 col-lg-8 col-xl-8">
                <p class="text-center">
                    <img src="images/logo-session.png" width="68" height="100" alt="Firefly III Logo" title="Firefly III"/><br>
                </p>
                <h1><strong>Firefly</strong> III - @yield('status_code') @yield('status') :(</h1>
                <h2 class="text-danger">@yield('sub_title')</h2>
                @yield('content')
            </div>
        </div>
    </div>
</main>
@vite(['js/pages/generic.js'])

</body>
</html>
