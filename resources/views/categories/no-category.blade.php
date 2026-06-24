@extends('layout.v3.session')
@section('content')

    {{-- upper show-all instruction --}}
    @if(count($periods) > 0)
        <div class="row">
            <div class="offset-lg-10 col-lg-2 offset-md-9 col-md-3 col-sm-12 col-xs-12">
                <p class="small text-center"><a href="{{ route('categories.no-category.all') }}">{{ __('firefly.showEverything') }}</a></p>
            </div>
        </div>
    @endif

    <div class="row">
        <div class="@if(count($periods) > 0) col-lg-10 col-md-9 col-sm-12 col-xs-12 @else col-lg-12 col-md-12 col-sm-12 col-xs-12 @endif">
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">{{ $subTitle }}</h3>
                </div>
                <div class="box-body ">

                    @if(count($periods) > 0)
                        <x-lists.groups-large :groups="$groups" />
                        <p>
                            <span class="bi bi-calendar"></span>
                            <a href="{{ route('categories.no-category.all') }}">{{ __('firefly.show_all_no_filter') }}</a>
                        </p>
                    @else
                        <x-lists.groups-large :groups="$groups" />
                        <p>
                            <span class="bi bi-calendar"></span>
                            <a href="{{ route('categories.no-category') }}">{{ __('firefly.show_the_current_period_and_overview') }}</a>
                        </p>
                    @endif
                </div>
            </div>
        </div>

        @if(count($periods) > 0)
            <div class="col-lg-2 col-md-4 col-sm-12 col-xs-12">
                <x-lists.periods :periods="$periods" />
            </div>
        @endif

    </div>

    {{-- lower show-all instruction --}}
    @if(count($periods) > 0)
        <div class="row">
            <div class="offset-lg-10 col-lg-2 offset-md-9 col-md-3 col-sm-12 col-xs-12">
                <p class="small text-center"><a href="{{ route('categories.no-category',['all']) }}">{{ __('firefly.showEverything') }}</a></p>
            </div>
        </div>
    @endif

@endsection
@section('scripts')
    @vite(['js/pages/generic.js'])
    {{-- required for groups.twig --}}
    <script type="text/javascript" src="v1/js/ff/list/groups.js?v={{ $FF_BUILD_TIME }}" nonce="{{ $JS_NONCE }}"></script>
@endsection
