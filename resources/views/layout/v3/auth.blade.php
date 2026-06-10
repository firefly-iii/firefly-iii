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

    <!--begin::Theme Init (prevents flash of incorrect theme on load, #6043)-->
    <!--end::Theme Init-->

    <!--begin::Accessibility Meta Tags-->
    <meta name="viewport" content="width=device-width, initial-scale=1.0, user-scalable=yes"/>
    <meta name="theme-color" content="#007bff" media="(prefers-color-scheme: light)"/>
    <meta name="theme-color" content="#1a1a1a" media="(prefers-color-scheme: dark)"/>
    <!--end::Accessibility Meta Tags-->

    <!--begin::Accessibility Features-->
    <!-- Skip links will be dynamically added by accessibility.js -->
    <meta name="color-scheme" content="light dark">

    <x-layout.fav-icons/>
    <!--end::Accessibility Features-->

</head>
<!--end::Head-->
<!--begin::Body-->
<body class="login-page bg-body-secondary">
<div class="login-box">
    <div class="card card-outline card-primary">
        <div class="card-header">
            <a
                href="../index2.html"
                class="link-dark text-center link-offset-2 link-opacity-100 link-opacity-50-hover"
            >
                <h1 class="mb-0"><b>Admin</b>LTE</h1>
            </a>
        </div>
        <div class="card-body login-card-body">
            <p class="login-box-msg">Sign in to start your sessionXXX</p>



                @yield('content')


            <!-- begin niuwe code  -->
        </div>
        <!-- /.login-box -->
    </div>
</div>

@yield('scripts')

</body>
<!--end::Body-->
</html>
