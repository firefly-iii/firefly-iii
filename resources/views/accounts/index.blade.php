@extends('layout.v3.session')
@section('content')
    @if(count($accounts) > 0)
        <div class="row">
            <div class="col-lg-12 col-md-12 col-sm-12">
                <div class="card" id="account-index-{{ $objectType }}">
                    <div class="card-header with-border">
                        <h3 class="card-title">
                            {{ $subTitle }}
                        </h3>
                        <div class="card-tools pull-right">
                            <div class="btn-group">
                                <button class="btn btn-box-tool dropdown-toggle" data-toggle="dropdown"><span class="fa fa-ellipsis-v"></span></button>
                                <ul class="dropdown-menu" role="menu">
                                    <li><a href="{{ route('accounts.create', $objectType) }}"><span
                                                class="fa fa-plus fa-fw"></span> {{ __('firefly.make_new_'. $objectType . '_account') }}
                                        </a></li>
                                </ul>
                            </div>
                        </div>


                    </div>
                    <div class="card-body no-padding">
                        <p>
                            <a href="{{ route('accounts.create', $objectType) }}" class="btn btn-success"><span class="bi bi-plus-circle"></span> {{ __('firefly.make_new_' . $objectType . '_account') }}</a>
                        </p>
                        <x-lists.accounts :accounts="$accounts" :objectType="$objectType" />
                    </div>
                    <div class="card-footer">
                        <p>
                            <a href="{{ route('accounts.create', $objectType) }}" class="btn btn-success"><span class="bi bi-plus-circle"></span> {{ __('firefly.make_new_' . $objectType . '_account') }}</a>
                        </p>
                        @if($inactiveCount > 0)
                            <p><small>
                                    <em>
                                        <a href="{{ route('accounts.inactive.index', $objectType) }}" class="text-muted">
                                            {{ trans_choice('firefly.inactive_account_link', $inactiveCount) }}
                                        </a>
                                    </em>
                                </small>
                            </p>
                        @endif
                        @if($inactivePage)
                            <p><small class="text-muted">
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
