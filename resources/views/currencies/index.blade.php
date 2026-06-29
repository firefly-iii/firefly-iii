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
                                <th>&nbsp;</th>
                                <th>{{ __('firefly.currency') }}</th>
                                <th>{{ __('firefly.number_of_decimals') }}</th>
                            </tr>
                            </thead>
                            <tbody>
                            @foreach($currencies as $currency)
                                <tr>
                                        <td>
                                            <div class="btn-group btn-group-sm">
                                                @if($isOwner)
                                                <a class="btn btn-outline-secondary" href="{{ route('currencies.edit',[$currency['id']]) }}"><span
                                                        class="bi bi-pencil"></span></a>
                                                <a class="btn btn-danger" href="{{ route('currencies.delete',[$currency['id']]) }}"><span
                                                        class="bi bi-trash"></span></a>
                                                @endif
                                                {{-- Disable the currency. --}}
                                                @if($currency->userGroupEnabled)
                                                    <a class="btn btn-outline-secondary disable-currency" data-code="{{ $currency['code'] }}"
                                                       href="#">
                                                        <span class="bi bi-app"></span>
                                                        {{ __('firefly.disable_currency') }}</a>
                                                @endif

                                                {{-- Enable the currency. --}}
                                                @if(!$currency->userGroupEnabled)
                                                    <a class="btn btn-outline-secondary enable-currency" data-code="{{ $currency['code'] }}"
                                                       href="#">
                                                        <span class="bi bi-check"></span>
                                                        {{ __('firefly.enable_currency') }}</a>
                                                @endif
                                            </div>
                                        </td>
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
