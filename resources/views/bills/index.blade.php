@extends('layout.v3.session')
@section('content')
    @if(0 === $total)
        @php
            $shownDemo = true
        @endphp
        <x-empty-page :route="route('subscriptions.create')" type="bills" object-type="default" />
    @endif
    @if($total > 0)
        <div class="row">
            <div class="col-lg-12 col-sm-12 col-md-12">
                <div class="card mb-2">
                    <div class="card-header">
                        <div class="row">
                            <div class="col">
                                <h3 class="card-title">{{ $title }}</h3>
                            </div>
                            <div class="col text-end">
                                <div class="dropdown">
                                    <button class="btn btn-sm btn-secondary dropdown-toggle" type="button" id="dropdownMenuButton1" data-bs-toggle="dropdown" aria-expanded="false">
                                        <span class="bi bi-list"></span>
                                    </button>
                                    <ul class="dropdown-menu" aria-labelledby="dropdownMenuButton1">
                                        <li><a class="dropdown-item" href="{{ route('subscriptions.create') }}"><span
                                                    class="bi bi-plus-circle"></span> {{ __('firefly.create_new_bill') }}
                                            </a></li>
                                    </ul>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="card-body p-0">
                        <x-lists.subscriptions :bills="$bills" :sums="$sums" :totals="$totals" />
                    </div>
                    <div class="card-footer">
                        <div class="row">
                            <div class="col text-end">
                                <div class="dropdown">
                                    <button class="btn btn-sm btn-secondary dropdown-toggle" type="button" id="dropdownMenuButton1" data-bs-toggle="dropdown" aria-expanded="false">
                                        <span class="bi bi-list"></span>
                                    </button>
                                    <ul class="dropdown-menu" aria-labelledby="dropdownMenuButton1">
                                        <li><a class="dropdown-item" href="{{ route('subscriptions.create') }}"><span
                                                    class="bi bi-plus-circle"></span> {{ __('firefly.create_new_bill') }}
                                            </a></li>
                                    </ul>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
@endif
@endsection
@section('scripts')
    @vite(['js/pages/subscriptions/index.js'])
    <script src="v1/js/lib/jquery-ui.min.js?v={{ $FF_BUILD_TIME }}" type="text/javascript" nonce="{{ $JS_NONCE }}"></script>
    <script type="text/javascript" src="v1/js/ff/bills/index.js?v={{ $FF_BUILD_TIME }}" nonce="{{ $JS_NONCE }}"></script>
@endsection
