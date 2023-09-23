@extends('layout.v2')
@section('vite')
    @vite(['resources/assets/v2/sass/app.scss', 'resources/assets/v2/pages/dashboard/dashboard.js'])
@endsection
@section('content')

    <div class="app-content">
        <!--begin::Container-->
        <div class="container-fluid">
            @include('partials.dashboard.boxes')

            <!-- row with account, budget and category data -->
            <div class="row mb-2" x-data="accounts">
                <!-- column with 3 charts -->
                <div class="col-xl-8 col-lg-12 col-sm-12 col-xs-12">
                    <!-- row with account chart -->
                    @include('partials.dashboard.account-chart')
                    <!-- row with budget chart -->
                    @include('partials.dashboard.budget-chart')
                    <!-- row with category chart -->
                    @include('partials.dashboard.category-chart')
                </div>
                <div class="col-xl-4 col-lg-12 col-sm-12 col-xs-12">
                    <!-- row with accounts list -->
                    @include('partials.dashboard.account-list')
                </div>

            </div>
            <!-- row with sankey chart -->
            <div class="row mb-2">
                @include('partials.dashboard.sankey')
            </div>
            <!-- row with piggy banks, subscriptions and empty box -->
            <div class="row mb-2">
                <!-- column with subscriptions -->
                @include('partials.dashboard.subscriptions')
                <!-- column with piggy banks -->
                @include('partials.dashboard.piggy-banks')
                <!-- column with to do things -->
                <div class="col">
                    <div class="card">
                        <div class="card-header">
                            <h3 class="card-title"><a href="#" title="Something">recurring? rules? tags?</a></h3>
                        </div>
                        <div class="card-body">
                            <p>
                                TODO
                            </p>
                        </div>
                    </div>
                </div>
            </div>

        </div>

    </div>

@endsection
