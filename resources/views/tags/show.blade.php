@extends('layout.v3.session')
@section('content')
    <div class="row">
        <div class="col-lg-6 col-md-6 col-sm-12 col-xs-12">
            <div class="card mb-2">
                <div class="card-header">
                    <h3 class="card-title">{{ __('firefly.meta_data') }}</h3>


                    <div class="box-tools text-end">
                        <div class="btn-group">
                            <button class="btn btn-box-tool dropdown-toggle" data-toggle="dropdown"><span
                                    class="bi bi-list"></span></button>
                            <ul class="dropdown-menu" role="menu">
                                <li><a href="{{ route('tags.edit',$tag->id) }}"><span
                                            class="bi bi-pencil"></span> {{ trans('firefly.edit_tag',['tag' => $tag->tag]) }}
                                    </a></li>
                                <li><a href="{{ route('tags.delete',$tag->id) }}"><span
                                            class="bi bi-trash"></span> {{ trans('firefly.delete_tag',['tag' => $tag->tag]) }}
                                    </a></li>
                            </ul>
                        </div>
                    </div>
                </div>
                <div class="card-body p-0">
                    <table class="table table-bordered">
                        @if(null !==$tag->description)
                            <tr>
                                <td>
                                    {{ trans('list.description') }}
                                </td>
                                <td>{{ $tag->description }}</td>
                            </tr>
                        @endif
                        @if(null !== $tag->date)
                            <tr>
                                <td>
                                    {{ trans('list.date') }}
                                </td>
                                <td>
                                    {{ $tag->date->isoFormat($monthAndDayFormat) }}
                                </td>
                            </tr>
                        @endif

                        {{-- total amount --}}
                        @php
                            $currentSum = '0';
                        @endphp
                        @foreach($sums as $set)
                            @php
                                $currentSum = bcadd($currentSum, bcadd(bcadd($set['Withdrawal'], $set['Transfer']), $set['Deposit']));
                            @endphp
                        @endforeach
                        @if(0 !== bccomp($currentSum, '0'))
                            <tr>
                                <td class="forty">{{ trans('list.sum') }}</td>
                                <td>
                                    @foreach($sums as $set)
                                        {!! format_amount_by_symbol(bcadd(bcadd($set['Withdrawal'], $set['Transfer']), $set['Deposit']), $set['currency_symbol'], $set['currency_decimal_places'], true) !!}{{ $loop->index !== count($sums) ? ',':'' }}
                                    @endforeach
                                </td>
                            </tr>
                        @endif

                        {{-- total expense excl. transfer --}}
                        @php
                            $currentSum = '0';
                        @endphp
                        @foreach($sums as $set)
                            @php
                                $currentSum = bcadd($currentSum, bcadd($set['Withdrawal'], $set['Deposit']));
                            @endphp
                        @endforeach
                        @if(0 !== bccomp($currentSum, '0'))
                            <tr>
                                <td class="forty">{{ trans('list.sum_excluding_transfers') }}</td>
                                <td>
                                    @foreach($sums as $set)
                                        {!! format_amount_by_symbol(bcadd($set['Withdrawal'], $set['Deposit']), $set['currency_symbol'], $set['currency_decimal_places'], true) !!}{{ $loop->index !== count($sums) ? ',':'' }}
                                    @endforeach
                                </td>
                            </tr>
                        @endif

                        {{-- withdrawals --}}
                        @php
                            $currentSum = '0';
                        @endphp
                        @foreach($sums as $set)
                            @php
                                $currentSum = bcadd($currentSum, $set['Withdrawal']);
                            @endphp
                        @endforeach
                        @if(0 !== bccomp($currentSum, '0'))
                            <tr>
                                <td class="forty">{{ trans('list.sum_withdrawals') }}</td>
                                <td>
                                    @foreach($sums as $set)
                                        {!! format_amount_by_symbol($set['Withdrawal'], $set['currency_symbol'], $set['currency_decimal_places'], true) !!}{{ $loop->index !== count($sums) ? ',':'' }}
                                    @endforeach
                                </td>
                            </tr>
                        @endif

                        {{-- deposits --}}
                        @php
                            $currentSum = '0';
                        @endphp
                        @foreach($sums as $set)
                            @php
                                $currentSum = bcadd($currentSum, $set['Deposit']);
                            @endphp
                        @endforeach
                        @if(0 !== bccomp($currentSum, '0'))
                            <tr>
                                <td class="forty">{{ trans('list.sum_deposits') }}</td>
                                <td>
                                    @foreach($sums as $set)
                                        {!! format_amount_by_symbol($set['Deposit'], $set['currency_symbol'], $set['currency_decimal_places'], true) !!}{{ $loop->index !== count($sums) ? ',':'' }}
                                    @endforeach
                                </td>
                            </tr>
                        @endif

                        {{-- transfers --}}
                        @php
                            $currentSum = '0';
                        @endphp
                        @foreach($sums as $set)
                            @php
                                $currentSum = bcadd($currentSum, $set['Transfer']);
                            @endphp
                        @endforeach
                        @if(0 !== bccomp($currentSum, '0'))
                            <tr>
                                <td class="forty">{{ trans('list.sum_transfers') }}</td>
                                <td>
                                    @foreach($sums as $set)
                                        {!! format_amount_by_symbol($set['Transfer'], $set['currency_symbol'], $set['currency_decimal_places'], true) !!}{{ $loop->index !== count($sums) ? ',':'' }}
                                    @endforeach
                                </td>
                            </tr>
                        @endif

                        {{-- reconciliation --}}
                        @php
                            $currentSum = '0';
                        @endphp
                        @foreach($sums as $set)
                            @php
                                $currentSum = bcadd($currentSum, $set['Reconciliation']);
                            @endphp
                        @endforeach
                        @if(0 !== bccomp($currentSum, '0'))
                            <tr>
                                <td class="forty">{{ trans('list.sum_reconciliations') }}</td>
                                <td>
                                    @foreach($sums as $set)
                                        {!! format_amount_by_symbol($set['Reconciliation'], $set['currency_symbol'], $set['currency_decimal_places'], true) !!}{{ $loop->index !== count($sums) ? ',':'' }}
                                    @endforeach
                                </td>
                            </tr>
                        @endif
                    </table>
                </div>
                <div class="card-footer">
                    <div class="btn-group btn-group-sm">
                        <a href="{{ route('tags.edit',$tag->id) }}" class="btn btn-outline-secondary"><span
                                class="bi bi-pencil"></span></a>
                        <a href="{{ route('tags.delete',$tag->id) }}" class="btn btn-danger"><span
                                class="bi bi-trash"></span></a>
                    </div>
                    <p class="text-muted">
                        <small>{{ __('firefly.sums_apply_to_range') }}</small>
                    </p>
                </div>
            </div>
        </div>
        <div class="col-lg-6 col-md-6 col-sm-12 col-xs-12">
            <div class="card mb-2">
                <div class="card-header">
                    <h3 class="card-title">{{ __('firefly.location') }}</h3>
                    <div class="box-tools text-end">
                        <div class="btn-group">
                            <button class="btn btn-box-tool dropdown-toggle" data-toggle="dropdown"><span
                                    class="bi bi-list"></span></button>
                            <ul class="dropdown-menu" role="menu">
                                <li><a href="{{ route('tags.edit',$tag->id) }}"><span
                                            class="bi bi-pencil"></span> {{ trans('firefly.edit_tag',['tag' => $tag->tag]) }}
                                    </a></li>
                                <li><a href="{{ route('tags.delete',$tag->id) }}"><span
                                            class="bi bi-trash"></span> {{ trans('firefly.delete_tag',['tag' => $tag->tag]) }}
                                    </a></li>
                            </ul>
                        </div>
                    </div>
                </div>
                <div class="card-body">
                    @if($location)
                        <div id="location_map" class="map-size"></div>
                    @else
                        <p>{{ __('firefly.no_location_set') }}</p>
                    @endif
                </div>
            </div>
        </div>
    </div>
    @if($attachments->count() > 0)
        <div class="row">
            <div class="col-lg-6 col-md-6 col-sm-12 col-xs-12">
                <div class="card mb-2">
                    <div class="card-header">
                        <h3 class="card-title">
                            {{ __('firefly.attachments') }}
                        </h3>
                    </div>
                    <div class="card-body p-0">
                        <x-lists.attachments :attachments="$attachments" />
                    </div>
                </div>
            </div>
        </div>
    @endif

    @if(count($periods) > 0)
        <div class="row">
            <div class="offset-lg-10 col-lg-2 offset-md-10 col-md-2 col-sm-12 col-xs-12">
                <p class="small text-center"><a
                        href="{{ route('tags.show',[$tag->id,'all']) }}">{{ __('firefly.showEverything') }}</a></p>
            </div>
        </div>
    @endif
    <div class="row">
        <div class="@if(count($periods) > 0) col-lg-10 col-md-10 col-sm-12 col-xs-12 @else col-lg-12 col-md-12 col-sm-12 col-xs-12 @endif">
            <div class="card mb-2">
                <div class="card-header">
                    <h3 class="card-title">{{ __('firefly.transactions') }}</h3>


                    <div class="box-tools text-end">
                        <div class="btn-group">
                            <button class="btn btn-box-tool dropdown-toggle" data-toggle="dropdown"><span
                                    class="bi bi-list"></span></button>
                            <ul class="dropdown-menu" role="menu">
                                <li><a href="{{ route('tags.edit',$tag->id) }}"><span
                                            class="bi bi-pencil"></span> {{ trans('firefly.edit_tag',['tag' => $tag->tag]) }}
                                    </a></li>
                                <li><a href="{{ route('tags.delete',$tag->id) }}"><span
                                            class="bi bi-trash"></span> {{ trans('firefly.delete_tag',['tag' => $tag->tag]) }}
                                    </a></li>
                            </ul>
                        </div>
                    </div>
                </div>
                <div class="card-body">
                    <x-lists.groups-large :groups="$groups" :show-budget="true" :show-category="true" />
                    @if(count($periods) > 0)
                        <p>
                            <span class="bi bi-calendar"></span>
                            <a href="{{ route('tags.show', [$tag->id,'all']) }}">
                                {{ __('firefly.show_all_no_filter') }}
                            </a>
                        </p>
                    @else
                        <p>
                            <span class="bi bi-calendar"></span>
                            <a href="{{ route('tags.show', [$tag->id]) }}">
                                {{ __('firefly.show_the_current_period_and_overview') }}
                            </a>
                        </p>
                    @endif
                </div>
            </div>
        </div>
        @if(count($periods) > 0)
            <div class="col-lg-2 col-md-2 col-sm-12 col-xs-12">
                <x-lists.periods :periods="$periods" />
            </div>
        @endif
    </div>
    @if(count($periods) > 0)
        <div class="row">
            <div class="offset-lg-10 col-lg-2 offset-md-10 col-md-2 col-sm-12 col-xs-12">
                <p class="small text-center"><a href="{{ route('tags.show',[$tag->id]) }}">{{ __('firefly.showEverything') }}</a>
                </p>
            </div>
        </div>
    @endif

@endsection
@section('styles')
    <link rel="stylesheet" href="v1/lib/leaflet/leaflet.css?v={{ $FF_BUILD_TIME }}"/>
@endsection
@section('scripts')
    <script type="text/javascript" nonce="{{ $JS_NONCE }}">
        // location stuff
        @if($location)
            var latitude = {{ $location->latitude ?? "52.3167" }};
            var longitude = {{ $location->longitude ??"5.5500" }};
            var zoomLevel = {{ $location->zoom_level ?? "6" }};
        @endif
    </script>
    <script src="v1/lib/leaflet/leaflet.js?v={{ $FF_BUILD_TIME }}" nonce="{{ $JS_NONCE }}"></script>
    <script src="v1/js/ff/tags/show.js?v={{ $FF_BUILD_TIME }}" nonce="{{ $JS_NONCE }}"></script>
    <script type="text/javascript" src="v1/js/ff/list/groups.js?v={{ $FF_BUILD_TIME }}" nonce="{{ $JS_NONCE }}"></script>
@endsection
