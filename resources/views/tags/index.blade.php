@extends('layout.v3.session')
@section('content')
    @if(0 === $count)
        @php
            $shownDemo = true
        @endphp
        <x-empty-page :route="route('tags.create')" type="tags" object-type="default" />
    @endif
    <form action="{{ route('tags.mass-destroy') }}" method="post" x-data="index">
        <input type="hidden" name="_token" value="{{ csrf_token() }}"/>
        @foreach($tags as $period => $entries)
            @if(count($entries) > 0)
                <div class="row mb-2"  data-period="{{$period}}">
                    <div class="col-lg-12 col-md-12 col-sm-12 col-xs-12">
                        <div class="card mb-2">
                            <div class="card-header">
                                <div class="row">
                                    <div class="col">
                                        <h3 class="card-title">{{ 'no-date' === $period ? __('firefly.without_date') : $period }}</h3>
                                    </div>
                                        <div class="col text-end" role="group">
                                            <div class="btn-group">
                                                <button type="button" data-period="{{$period}}" class="btn btn-outline-secondary btn-sm select-all"><span data-period="{{$period}}" class="checkbox-icon bi bi-check-square"></span> <span data-period="{{$period}}" class="select-all-text">{{ __('firefly.multi_select_select_all') }}</span></button>
                                            <a href="{{ route('tags.create') }}" class="btn btn-sm btn-outline-success">
                                                <span class="bi bi-plus-circle"></span> {{ __('firefly.no_tags_create_default') }}
                                            </a>
                                            </div>
                                        </div>
                                </div>
                            </div>

                            <div class="card-body">
                                <div class="row">
                                    @foreach($entries as $tagInfo)
                                        <div class="col-3 big-line">
                                            <input type="checkbox" name="tags[]" value="{{ $tagInfo['id'] }}">
                                            <a class="badge text-bg-success text-truncate d-inline-block"

                                                title="{{ $tagInfo['created_at']->isoFormat($monthAndDayFormat) }}"
                                                href="{{ route('tags.show',[$tagInfo['id']]) }}">@if(null !== $tagInfo['location'])<span class="bi bi-geo-alt"></span>@endif<span class="bi bi-tag"></span> @if(strlen($tagInfo['tag']) > 45){{ substr($tagInfo['tag'],0,45) }}...@else{{ $tagInfo['tag'] }}@endif @if($tagInfo['attachments']->count() > 0)<span class="bi bi-paperclip"></span>@endif</a></div>
                                    @endforeach
                                </div>
                            </div>
                            <div class="card-footer">
                                <div class="col text-end">
                                    <div class="btn-group">
                                    <button type="button" data-period="{{$period}}" class="btn btn-outline-secondary btn-sm select-all"><span data-period="{{$period}}" class="checkbox-icon bi bi-check-square"></span>
                                        <span data-period="{{$period}}" class="select-all-text">{{ __('firefly.multi_select_select_all') }}</span></button>
                                    <a href="{{ route('tags.create') }}" class="btn btn-sm btn-outline-success">
                                        <span class="bi bi-plus-circle"></span> {{ __('firefly.no_tags_create_default') }}
                                    </a>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            @endif
        @endforeach
        <div class="row">
            <div class="col-lg-12 col-md-12 col-sm-12 col-xs-12">
                <p class="text-end">
                    <button type="submit" class="btn btn-danger confirm-tag-delete">
                        <span class="bi bi-trash"></span> {{ __('firefly.delete_all_selected_tags') }}
                    </button>
                </p>
            </div>
        </div>
    </form>

@endsection
@section('scripts')
    @vite(['js/pages/tags/index.js'])
    <script nonce="{{ $JS_NONCE }}">
        var confirmText = '{{ e(__('firefly.are_you_sure')) }}';
    </script>
    <script src="v1/js/ff/tags/index.js?v={{ $FF_BUILD_TIME }}" nonce="{{ $JS_NONCE }}"></script>
@endsection
