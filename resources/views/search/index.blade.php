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
                        {!! __('firefly.search_box_intro') !!}
                    </p>
                    {{-- search form --}}
                    <form class="form-horizontal" action="{{ route('search.index') }}" method="get">
                        <div class="row mb-3">
                            <label for="inputEmail3" class="col-sm-2 col-form-label">{{ __('firefly.search_query') }}</label>
                            <div class="col-sm-10">
                                <input autocomplete="off" type="text" name="search" id="query" value="{{ $fullQuery }}" class="form-control" spellcheck="false"
                                       placeholder="{{ $fullQuery }}">
                            </div>
                        </div>
                        <div class="row mb-3">
                            <div class="offset-md-2 col-sm-10">
                                <div class="btn-group">
                                <button type="submit" class="btn btn-info"><span class="bi bi-search"></span> {{ __('firefly.search') }}</button>
                                @if($ruleId > 0 && $ruleChanged)
                                    <a href="{{ route('rules.edit', [$ruleId]) }}?from_query={{ $fullQuery }}"
                                       class="btn btn-outline-secondary">{{ trans('firefly.update_rule_from_query', ['rule' => $rule->title]) }}</a>
                                @endif
                                <a href="{{ route('rules.create') }}?from_query={{ $fullQuery }}" class="btn btn-outline-secondary">{{ __('firefly.create_rule_from_query') }}</a>
                                </div>
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
                            {!! trans('firefly.search_for_query',['query' => join(' ',array_map(function(string $value): string {return sprintf('<span class="search-word">%s</span>', $value);}, $words))]) !!}
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
                        @if(array_key_exists('prohibited', $operator) && $operator['prohibited'])
                            <li>{{ trans('firefly.search_modifier_not_' . $operator['type'], ['value' => $operator['value']]) }}</li>
                        @endif
                        @if(array_key_exists('prohibited', $operator) && !$operator['prohibited'])
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
                <div class="card mb-2 search_box">
                    <div class="card-header">
                        <h3 class="card-title">{{ __('firefly.transactions') }}</h3>
                    </div>
                    <div class="card-body">
                        <p class="search_ongoing text-center">
                            {{ __('firefly.search_searching') }}
                        </p>
                        <div class="search_results d-none"></div>
                        {{-- loading indicator --}}
                        <div class="overlay">
                            <div class="spinner-border spinner-border-sm" role="status">
                                <span class="visually-hidden">Loading...</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="row error_row d-none">
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

        var searchQuery = "{!! escape_for_js($fullQuery) !!}";
        var searchUrl = "{{ route('search.search') }}?page={{ $page }}";
        var searchPage = {{ $page }};
        var cloneGroupUrl = '{{ route('transactions.clone') }}';
        var cloneAndEditUrl = '{{ route('transactions.clone') }}?redirect=edit';
    </script>
    {{-- required for groups.twig --}}
    <script type="text/javascript" src="v1/js/ff/list/groups.js?v={{ $FF_BUILD_TIME }}" nonce="{{ $JS_NONCE }}"></script>
    <script type="text/javascript" src="v1/js/ff/search/index.js?v={{ $FF_BUILD_TIME }}" nonce="{{ $JS_NONCE }}"></script>

@endsection
