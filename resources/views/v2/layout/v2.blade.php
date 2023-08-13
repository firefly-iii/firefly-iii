<!DOCTYPE html>
<html lang="{{ trans('config.html_language') }}">
<!--  data-bs-theme="dark" -->
<!--begin::Head-->
@include('partials.layout.head')
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
                        <em class="fa-solid fa-bars"></em>
                    </a>
                </li>
                <!--begin::Navbar Search-->
                <li class="nav-item">
                    <a class="nav-link" data-widget="navbar-search" href="#" role="button">
                        <em class="fa-solid fa-magnifying-glass"></em>
                    </a>
                </li>
                <!--end::Navbar Search-->
            </ul>
            <!--end::Start Navbar Links-->

            <!--begin::End Navbar Links-->
            <ul class="navbar-nav ms-auto" x-data="dates">

                <!-- begin date range drop down -->
                <li class="nav-item dropdown">
                    <a class="nav-link daterange-holder" data-bs-toggle="dropdown" href="#"></a>
                    <div class="dropdown-menu dropdown-menu-lg dropdown-menu-end">
                        <a href="#" class="dropdown-item daterange-current" @click="changeDateRange">

                        </a>
                        <div class="dropdown-divider"></div>
                        <a href="#" @click="changeDateRange" class="dropdown-item daterange-next">
                            next
                        </a>
                        <div class="dropdown-divider"></div>
                        <a href="#" class="dropdown-item daterange-prev" @click="changeDateRange">
                            prev
                        </a>
                        <div class="dropdown-divider"></div>
                        <a href="#" class="dropdown-item daterange-7d" @click="changeDateRange">
                            {{ __('firefly.last_seven_days') }}
                        </a>
                        <div class="dropdown-divider"></div>
                        <a href="#" class="dropdown-item daterange-90d" @click="changeDateRange">
                            {{ __('firefly.last_thirty_days') }}
                        </a>
                        <div class="dropdown-divider"></div>
                        <a href="#" class="dropdown-item daterange-mtd" @click="changeDateRange">
                            {{ __('firefly.month_to_date') }}
                        </a>
                        <div class="dropdown-divider"></div>
                        <a href="#" class="dropdown-item daterange-ytd" @click="changeDateRange">
                            {{ __('firefly.year_to_date') }}
                        </a>
                        <div class="dropdown-divider"></div>
                        <a href="#" class="dropdown-item dropdown-footer daterange-custom" @click="app.doCustomRange">
                            TODO {{ __('firefly.customRange') }}
                        </a>
                    </div>
                </li>
                <!-- end date range drop down -->
                <!-- user menu -->
                @include('partials.layout.topbar')
            </ul>
            <!--end::End Navbar Links-->
        </div>
        <!--end::Container-->
    </nav>
    <!--end::Header-->
    <!--begin::Sidebar-->
    @include('partials.layout.sidebar')
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
                            @if($mainTitleIcon)
                                <em class="fa {{ $mainTitleIcon }}"></em>
                            @endif
                            {{ $title }} @if($subTitle)
                                <small class="text-muted">
                                    {{$subTitle}}</small>
                            @endif</h3>
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
        @yield('content')
        <!--end::App Content-->
    </main>
    <!--end::App Main-->

    <!--begin::Footer-->
    @include('partials.layout.footer')

    <!--end::Footer-->
</div>
<!--end::App Wrapper-->
<!--begin::Script-->
@include('partials.layout.scripts')
<!--end::Script-->

</body>
<!--end::Body-->

</html>
