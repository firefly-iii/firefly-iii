@extends('layout.v3.session')
@section('content')
    @if(count($accounts) > 0)
        <div class="row">
            <div class="col-lg-12 col-md-12 col-sm-12">
                <div class="card" id="account-index-{{ $objectType }}">
                    <x-elements.card-header-with-menu :cardTitle="trans('firefly.'.$objectType.'_accounts')" :route="route('accounts.create', $objectType)" :linkTitle="__('firefly.make_new_'. $objectType . '_account')"/>
                    <div class="card-body p-0">
                        <x-lists.accounts :accounts="$accounts" :objectType="$objectType" />
                    </div>
                    <x-elements.card-footer-with-menu :route="route('accounts.create', $objectType)" :linkTitle="__('firefly.make_new_'. $objectType . '_account')" />
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
                    <p class="m-2"><small class="text-muted">
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
        @php
            $shownDemo = true
        @endphp
        <x-empty-page :route="route('accounts.create', [$objectType])" type="accounts" :object-type="$objectType" />
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
