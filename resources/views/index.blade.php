@extends('layout.v4.default')
@section('vite')
    @vite(['resources/assets/v4/sass/app.scss', 'resources/assets/v4/app.js', 'resources/assets/v4/index.js'])
@endsection
@section('content')

    <div class="app-content">
        <!--begin::Container-->
        <div class="container-fluid">
            @include('partials.dashboard.boxes')


        </div>

    </div>

@endsection
