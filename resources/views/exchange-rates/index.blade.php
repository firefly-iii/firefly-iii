@extends('layout.v3.session')
@section('content')
<div id="exchange_rates_index"></div>
@endsection
@section('scripts')
    @vite(['js/pages/exchange-rates/index.js'])
@endsection
