@extends('layout.v3.session')
@section('content')
    @if(0 === $total)
        @php
            $shownDemo = true
        @endphp
        <x-empty-page :route="route('subscriptions.create')" type="bills" object-type="default" />
    @endif
    @if($total > 0)


                        <x-lists.subscriptions :bills="$bills" :sums="$sums" :totals="$totals" />

@endif
@endsection
@section('scripts')
    @vite(['js/pages/generic.js'])
    <script src="v1/js/lib/jquery-ui.min.js?v={{ $FF_BUILD_TIME }}" type="text/javascript" nonce="{{ $JS_NONCE }}"></script>
    <script type="text/javascript" src="v1/js/ff/bills/index.js?v={{ $FF_BUILD_TIME }}" nonce="{{ $JS_NONCE }}"></script>
@endsection
