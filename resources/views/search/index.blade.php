@extends('layout.v3.session')
@section('breadcrumbs')
{{ Breadcrumbs::render(Route::getCurrentRoute()->getName(), $fullQuery) }}

@endsection
@section('content')
    <div class="row">
        <div class="col-lg-12">
            <div class="card mb-2">
                <div class="card-header">
                    <h3 class="card-title">{{ __('firefly.search_box') }}</h3>
                </div>
                <div class="card-body">
                    <p>
                        {{ __('firefly.search_box_intro') }}
                    </p>
                    {{-- search form --}}
                    <form class="form-horizontal" action="{{ route('search.index') }}" method="get">
                        <div class="form-group">
                            <label for="query" class="col-sm-1 control-label">{{ __('firefly.search_query') }}</label>
                            <div class="col-sm-10">
                                <input autocomplete="off" type="text" name="search" id="query" value="{{ $fullQuery }}" class="form-control" spellcheck="false"
                                       placeholder="{{ $fullQuery }}">
                            </div>
                        </div>
                        <div class="form-group">
                            <div class="col-sm-offset-1 col-sm-10">
                                <button type="submit" class="btn btn-info"><span class="fa fa-search"></span> {{ __('firefly.search') }}</button>
                                @if($ruleId > 0 && $ruleChanged)
                                    <a href="{{ route('rules.edit', [$ruleId]) }}?from_query={{ $fullQuery }}"
                                       class="btn btn-outline-secondary">{{ trans('firefly.update_rule_from_query', ['rule' => $rule->title]) }}</a>
                                @endif
                                <a href="{{ route('rules.create') }}?from_query={{ $fullQuery }}" class="btn btn-outline-secondary">{{ __('firefly.create_rule_from_query') }}</a>
                            </div>
                        </div>
                        @if(0 !== $ruleId)
                            <input type="hidden" name="rule" value="{{ $ruleId }}"/>
                        @endif
                    </form>
                     <p>
                            {!! trans('firefly.search_for_overview') !!}
                    </p>
                    <ul>
                    @if(count($words) > 0)
                        <li>
                            {!! trans('firefly.search_for_query',
                                    [
                                'query' => join(' ',array_map(function(string $value): string {return sprintf('<span class="search-word">%s</span>', $value);}, $words))])
                            !!}
                        </li>
                    @endif
                    @if(count($excludedWords) > 0)
                        <li>
                            {!! trans('firefly.search_for_excluded_words',
                                    [
                                'excluded_words' => join(' ',array_map(function(string $value): string {return sprintf('<span class="search-word">%s</span>', $value);}, $excludedWords))])
                            !!}
                        </li>
                    @endif
                    @foreach($operators as $operator)
                        @if($operator['prohibited'])
                            <li>{{ trans('firefly.search_modifier_not_' . $operator['type'], ['value' => $operator['value']]) }}</li>
                        @endif
                        @if(!$operator['prohibited'])
                            <li>{{ trans('firefly.search_modifier_' . $operator['type'], ['value' => $operator['value']]) }}</li>
                        @endif
                    @endforeach
                    </ul>

                    @if(count($invalidOperators) > 0)
                        <p>{{ trans('firefly.invalid_operators_list') }}</p>
                        <ul>
                            @foreach($invalidOperators as $operator)
                                <li class="text-danger">{{ $operator['type'] }}:{{ $operator['value'] }}</li>
                            @endforeach
                        </ul>
                    @endif
                </div>
            </div>
        </div>
    </div>
    @if(strlen($fullQuery) > 0 || count($words) > 0 || count($excludedWords) > 0 || count($operators) > 0)
        <div class="row result_row">
            <div class="col-lg-12 col-md-12 col-sm-12">
                <div class="box search_box">
                    <div class="card-header">
                        <h3 class="card-title">{{ __('firefly.transactions') }}</h3>
                    </div>
                    <div class="card-body">
                        <p class="search_ongoing text-center">
                            {{ __('firefly.search_searching') }}
                        </p>
                        <div class="search_results hidden"></div>
                        {{-- loading indicator --}}
                        <div class="overlay">
                            <div class="spinner-border spinner-border-sm" role="status">
  <span class="visually-hidden">Loading...</span>
</div>
                        </div>

                        <div class="row mass_edit_all hidden-xs hidden">
                            <div class="col-lg-6 col-md-12 col-sm-12 col-xs-12">
                                <div class="mass_button_options btn-group btn-group hidden">
                                    <a href="#" class="btn btn-outline-secondary mass_edit"><span class="bi bi-pencil"></span> <span>{{ __('firefly.edit_selected') }}</span></a>
                                    <a href="#" class="btn btn-outline-secondary bulk_edit"><span>{{ __('firefly.bulk_edit') }}</span></a>
                                    <a href="#" class="btn btn-danger mass_delete"><span class="bi bi-trash"></span>
                                        <span>{{ __('firefly.delete_selected') }}</span></a>
                                </div>
                            </div>
                            <div class="col-lg-6 col-md-12 col-sm-12 col-xs-12 hidden-xs">

                                <div class="mass_buttons btn-group btn-group text-end">
                                    <a href="#" class="btn btn-outline-secondary mass_select"><span
                                            class="bi bi-check-square-o"></span> {{ __('firefly.select_transactions') }}</a>
                                    <a href="#" class="btn btn-outline-secondary mass_stop_select hidden"><span class="fa faw-fw fa-square-o"
                                        ></span> {{ __('firefly.stop_selection') }}</a>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="row error_row hidden">
            <div class="col-lg-12 col-md-12 col-sm-12">
                <div class="card card-danger card-outline">
                    <div class="card-header">
                        <h3 class="card-title">{{ __('firefly.search_error') }}</h3>
                    </div>
                    <div class="card-body">
                        <p class="search_ongoing">
                            {{ __('firefly.general_search_error') }}
                        </p>
                    </div>
                </div>
            </div>
        </div>
    @endif
    @if(strlen($fullQuery) === 0 && count($words) === 0 && count($excludedWords) === 0 && count($operators) === 0)
        <div class="row">
            <div class="col-lg-12 col-md-12 col-sm-12">
                <div class="card mb-2">
                    <div class="card-header">
                        <h3 class="card-title">{{ __('firefly.search_results') }}</h3>
                    </div>
                    <div class="card-body">
                        <p>{{ __('firefly.no_results_for_empty_search') }}</p>
                    </div>
                </div>
            </div>
        </div>
    @endif
@endsection
@section('scripts')
    @vite(['js/pages/generic.js'])
    <script type="text/javascript" nonce="{{ $JS_NONCE }}">
        var edit_selected_txt = "{{ trans('firefly.edit') }}";
        var delete_selected_txt = "{{ trans('firefly.delete') }}";
        var edit_bulk_selected_txt = "{{ trans('firefly.bulk_edit') }}";

        var searchQuery = "{{ $fullQuery }}";
        var searchUrl = "{{ route('search.search') }}?page={{ $page }}";
        var searchPage = {{ $page }};
        var cloneGroupUrl = '{{ route('transactions.clone') }}';
        var cloneAndEditUrl = '{{ route('transactions.clone') }}?redirect=edit';
    </script>
    {{-- required for groups.twig --}}
    <script type="text/javascript" src="v1/js/ff/list/groups.js?v={{ $FF_BUILD_TIME }}" nonce="{{ $JS_NONCE }}"></script>
    <script type="text/javascript" src="v1/js/ff/search/index.js?v={{ $FF_BUILD_TIME }}" nonce="{{ $JS_NONCE }}"></script>

@endsection
