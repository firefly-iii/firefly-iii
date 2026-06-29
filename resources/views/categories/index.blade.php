@extends('layout.v3.session')
@section('content')
    @if(0 === $categories->count() && 1 === $page)
        @php
            $shownDemo = true
        @endphp
        <x-empty-page :route="route('categories.create')" type="categories" object-type="default" />
    @endif
    @if($categories->count() > 0)
        <div class="row">
            <div class="col-lg-12 col-md-12 col-sm-12">
                <div class="card mb-2">
                    <x-elements.card-header-with-menu :cardTitle="trans('firefly.categories')" :route="route('categories.create')" :linkTitle="__('firefly.new_category')"/>

                    <div class="card-body p-0">
                        <x-lists.categories :categories="$categories" />
                    </div>
                    <x-elements.card-footer-with-menu :route="route('categories.create')" :linkTitle="__('firefly.new_category')" />
                </div>

            </div>
        </div>
    @endif
    @endsection

@section('styles')
<link href="v1/css/bootstrap-sortable.css?v={{ $FF_BUILD_TIME }}" type="text/css" rel="stylesheet" media="all" nonce="{{ $JS_NONCE }}">
@endsection

@section('scripts')
    @vite(['js/pages/generic.js'])
    <script type="text/javascript" src="v1/js/lib/bootstrap-sortable.js?v={{ $FF_BUILD_TIME }}" nonce="{{ $JS_NONCE }}"></script>
    <script type="text/javascript" src="v1/js/ff/categories/index.js?v={{ $FF_BUILD_TIME }}" nonce="{{ $JS_NONCE }}"></script>
@endsection
