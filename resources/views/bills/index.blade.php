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
                    <x-elements.card-header-with-menu :cardTitle="$title" :route="route('subscriptions.create')" :linkTitle="__('firefly.create_new_bill')" />
                    <div class="card-body p-0">
                        <x-lists.subscriptions :bills="$bills" :sums="$sums" :totals="$totals" />
                    </div>
                    <x-elements.card-footer-with-menu :route="route('subscriptions.create')" :linkTitle="__('firefly.create_new_bill')" />
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
