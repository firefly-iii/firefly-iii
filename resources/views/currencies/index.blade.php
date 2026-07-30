@extends('layout.v3.session')
@section('content')
    <div class="row">
        <div class="col-lg-12 col-sm-12 col-md-12">
            <div class="card mb-2">
                <x-elements.card-header-with-menu :cardTitle="__('firefly.currencies')" :route="route('currencies.create')" :linkTitle="__('firefly.create_currency')"/>
                <div class="card-body p-0">
                    <p class="m-2">
                        {{ __('firefly.currencies_intro') }}
                        {{ __('firefly.currencies_default_disabled') }}
                        {{ __('firefly.currencies_switch_default') }}
                    </p>
                    @if($currencies->count() > 0)
                        <div class="m-2">
                            {{ $currencies->links('pagination.bootstrap-4') }}
                        </div>
                        <table class="table table-striped table-hover">
                            <thead>
                            <tr>
                                <th>{{ __('firefly.currency') }}</th>
                                <th>{{ __('firefly.number_of_decimals') }}</th>
                                <th>&nbsp;</th>
                            </tr>
                            </thead>
                            <tbody>
                            @foreach($currencies as $currency)
                                <tr>

                                    <td>
                                        @if(!$currency->userGroupEnabled)<span class="text-muted">@endif
                                            {{ $currency->name }} ({{ $currency->code }}) ({{ $currency->symbol }})
                                            @if($currency->id === $primaryCurrency->id)
                                                <span class="badge text-bg-success" id="default-currency">{{ __('firefly.primary_currency_button') }}</span>
                                           @endif
                                            @if(!$currency->userGroupEnabled)<span class="badge text-bg-primary">{{ __('firefly.currency_is_disabled') }}</span></span>
                                        @endif
                                    </td>

                                    <td>{{ $currency->decimal_places }}</td>
                                    <td class="text-end">
                                        <div class="dropdown">
                                            <button class="btn btn-sm btn-outline-secondary dropdown-toggle" type="button" data-bs-toggle="dropdown" aria-expanded="false">
                                                {{ __('firefly.actions') }}
                                            </button>
                                            <ul class="dropdown-menu">
                                                @if($isOwner)
                                                    <li><a class="dropdown-item" href="{{ route('currencies.edit',[$currency['id']]) }}"><span class="bi bi-pencil"></span> {{ __('firefly.edit') }}</a></li>
                                                    <li><a class="dropdown-item text-danger" href="{{ route('currencies.delete',[$currency['id']]) }}"><span class="bi bi-trash"></span> {{ __('firefly.delete') }}</a></li>
                                                @endif
                                                @if($currency->userGroupEnabled)
                                                    <li><a class="dropdown-item disable-currency" href="#" data-code="{{ $currency['code'] }}"><span class="bi bi-app"></span> {{ __('firefly.disable_currency') }}</a></li>
                                                @endif
                                                @if(!$currency->userGroupEnabled)
                                                    <li><a class="dropdown-item enable-currency" href="#" data-code="{{ $currency['code'] }}"><span class="bi bi-check"></span> {{ __('firefly.enable_currency') }}</a></li>
                                                @endif
                                            </ul>
                                        </div>
                                    </td>
                                </tr>
                            @endforeach
                            </tbody>
                        </table>
                        <div class="m-1">
                            {{ $currencies->links('pagination.bootstrap-4') }}
                        </div>
                    @endif
                </div>
                <x-elements.card-footer-with-menu :route="route('currencies.create')" :linkTitle="__('firefly.create_currency')" />
            </div>
        </div>
    </div>
@endsection
@section('scripts')
    @vite(['js/pages/generic.js'])
    <script type="text/javascript" nonce="{{ $JS_NONCE }}">
        var redirectUrl = "{{ route('currencies.index') }}";
        var updateCurrencyUrl = "{{ route('api.v1.currencies.update', ['']) }}";
    </script>
    <script type="text/javascript" src="v1/js/ff/currencies/index.js?v={{ $FF_BUILD_TIME }}" nonce="{{ $JS_NONCE }}"></script>
@endsection
