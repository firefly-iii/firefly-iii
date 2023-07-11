@extends('layout.v4.default')
@section('content')

    <div class="app-content">
        <!--begin::Container-->
        <div class="container-fluid">
            Here be content.

            <!-- /.row (main row) -->
            <div x-data="{ count: 0 }">
                <button x-on:click="count++">Increment</button>

                <span x-text="count"></span>
            </div>

        </div>

        Icon: <i class="fa-solid fa-user"></i><br>
        <!-- uses solid style -->
        Icon: <i class="fa-brands fa-github-square"></i>
        <!--end::Container-->
    </div>

@endsection
