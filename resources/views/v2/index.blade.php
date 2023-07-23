@extends('layout.default')
@section('vite')
    @vite(['resources/assets/v2/sass/app.scss', 'resources/assets/v2/dashboard.js'])
@endsection
@section('content')

    <div class="app-content">
        <!--begin::Container-->
        <div class="container-fluid">
            @include('partials.dashboard.boxes')
            <!-- row with account data -->
            <div class="row">
                <div class="col-xl-8 col-lg-12 col-sm-12 col-xs-12">
                    Graph
                </div>
                <div class="col-xl-4 col-lg-12 col-sm-12 col-xs-12">
                    <div class="row">
                        <div class="col-12">
                            Account1
                        </div>
                        <div class="col-12">
                            Account2
                        </div>
                        <div class="col-12">
                            Account3
                        </div>
                        <div class="col-12">
                            Account4
                        </div>
                    </div>
                </div>

            </div>
        </div>

    </div>

@endsection
