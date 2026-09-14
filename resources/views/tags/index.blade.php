@extends('layout.v3.session')
@section('content')
    @if(0 === $count)
        @php
            $shownDemo = true
        @endphp
        <x-empty-page :route="route('tags.create')" type="tags" object-type="default" />
    @endif
    <form action="{{ route('tags.mass-destroy') }}" method="post">
        <input type="hidden" name="_token" value="{{ csrf_token() }}"/>
        @foreach($tags as $period => $entries)
            @if(count($entries) > 0)
                <div class="row mb-2">
                    <div class="col-lg-12 col-md-12 col-sm-12 col-xs-12">
                        <div class="card mb-2">
                            <x-elements.card-header-with-menu :cardTitle="'no-date' === $period ? __('firefly.without_date') : $period" :route="route('tags.create')" :linkTitle="__('firefly.no_tags_create_default')"/>
                            <div class="card-body">
                                <div class="row">
                                    @foreach($entries as $tagInfo)
                                        <div class="col-xs-6 col-sm-4 col-md-3 col-lg-2 big-line">
                                            <input type="checkbox" name="tags[]" value="{{ $tagInfo['id'] }}">
                                            <a
                                                class="badge text-bg-success text-truncate d-inline-block"

                                                title="{{ $tagInfo['created_at']->isoFormat($monthAndDayFormat) }}"
                                                href="{{ route('tags.show',[$tagInfo['id']]) }}">@if(null !== $tagInfo['location'])<span class="bi bi-geo-alt"></span>@endif<span class="bi bi-tag"></span> @if(strlen($tagInfo['tag']) > 20){{ substr($tagInfo['tag'],0,20) }}...@else{{ $tagInfo['tag'] }}@endif @if($tagInfo['attachments']->count() > 0)<span class="bi bi-paperclip"></span>@endif</a></div>
                                    @endforeach
                                </div>
                            </div>
                            <x-elements.card-footer-with-menu :route="route('tags.create')" :linkTitle="__('firefly.no_tags_create_default')" />
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
    @vite(['js/pages/generic.js'])
    <script nonce="{{ $JS_NONCE }}">
        var confirmText = '{{ e(__('firefly.are_you_sure')) }}';
    </script>
    <script src="v1/js/ff/tags/index.js?v={{ $FF_BUILD_TIME }}" nonce="{{ $JS_NONCE }}"></script>
@endsection
