@extends('layout.v3.session')
@section('content')
    @if(count($accounts) > 0)
        <div class="row">
            <div class="col-lg-12 col-md-12 col-sm-12">
                <div class="card" id="account-index-{{ $objectType }}">
                    <div class="card-header">
                        <div class="row">
                            <div class="col">
                                <div class="card-title">{{ trans('firefly.'.$objectType.'_accounts') }}</div>
                            </div>
                            <div class="col text-end">
                                <div class="dropdown">
                                    <button class="btn btn-sm btn-secondary dropdown-toggle" type="button" id="dropdownMenuButton1" data-bs-toggle="dropdown" aria-expanded="false">
                                        <span class="bi bi-list"></span>
                                    </button>
                                    <ul class="dropdown-menu" aria-labelledby="dropdownMenuButton1">
                                        <li><a class="dropdown-item" href="{{ route('accounts.create', $objectType) }}"><span
                                                    class="bi bi-plus-circle"></span> {{ __('firefly.make_new_'. $objectType . '_account') }}
                                            </a></li>
                                    </ul>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="card-body p-0">
                        <p class="m-2">
                            <a href="{{ route('accounts.create', $objectType) }}" class="btn btn-success"><span class="bi bi-plus-circle"></span> {{ __('firefly.make_new_' . $objectType . '_account') }}</a>
                        </p>
                        <x-lists.accounts :accounts="$accounts" :objectType="$objectType" />
                    </div>
                    <div class="card-footer p-0">
                        <p class="m-2">
                            <a href="{{ route('accounts.create', $objectType) }}" class="btn btn-success"><span class="bi bi-plus-circle"></span> {{ __('firefly.make_new_' . $objectType . '_account') }}</a>
                        </p>

                    </div>
                </div>
                @if($inactiveCount > 0 && !$inactivePage)
                    <p class="m-2"><small>
                            <em>
                                <a href="{{ route('accounts.inactive.index', $objectType) }}" class="text-muted">
                                    {{ trans_choice('firefly.inactive_account_link', $inactiveCount) }}
                                </a>
                            </em>
                        </small>
                    </p>
                @endif
                @if($inactivePage)
                    <p class="m-2""><small class="text-muted">
                            <em>
                                {{ trans('firefly.all_accounts_inactive') }}
                                <a href="{{ route('accounts.index', $objectType) }}">
                                    {{ trans('firefly.active_account_link', ['count' => $inactiveCount]) }}
                                </a>
                            </em>
                        </small>
                    </p>
                @endif
            </div>
        </div>
    @endif
    @if(0 === count($accounts) && 1 === $page)
        {% include 'partials.empty' with {objectType: objectType, type: 'accounts',route: route('accounts.create', [objectType])} %}

        @if($inactiveCount > 0)
            <p class="text-center"><small>
                    <em>
                        <a href="{{ route('accounts.inactive.index', $objectType) }}" class="text-muted">
                            {{ trans_choice('firefly.inactive_account_link', $inactiveCount) }}
                        </a>
                    </em>
                </small>
            </p>
        @endif

    @endif

@endsection
@section('scripts')
    @vite(['js/pages/accounts/index.js'])
    <script type="text/javascript" nonce="{{ $JS_NONCE }}">
        var objectType = '{{ e($objectType) }}';
    </script>
    <script src="v1/js/lib/jquery-ui.min.js?v={{ $FF_BUILD_TIME }}" type="text/javascript" nonce="{{ $JS_NONCE }}"></script>
    <script type="text/javascript" nonce="{{ $JS_NONCE }}" src="v1/js/ff/accounts/index.js?v={{ $FF_BUILD_TIME }}"></script>
@endsection
