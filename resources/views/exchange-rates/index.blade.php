@extends('layout.v3.session')
@section('content')
    <div x-data="index">
        <div class="row">
            <div class="col-lg-8 offset-lg-2 col-md-12 col-sm-12 col-xs-12">
                <div class="card mb-2">
                    <div class="card-header">
                        <h3 class="card-title">{{ __('firefly.header_exchange_rates') }}</h3>
                    </div>
                    <div class="card-body">
                        <p>{!! __('firefly.exchange_rates_intro')  !!}</p>
                    </div>
                </div>
            </div>
        </div>
        <div class="row">
            <div class="col-lg-8 offset-lg-2 col-md-12 col-sm-12 col-xs-12">
                <template x-if="currencies.length < 2">
                <template x-for="currency in currencies" :key="currency.id">
                <div class="card mb-2">
                    <div class="card-header">
                        <h3 class="card-title">{{ __('firefly.not_enough_currencies') }}</h3>
                    </div>
                    <div class="card-body">
                        <p>
                            {{ __('firefly.not_enough_currencies_enabled') }}
                        </p>
                    </div>
                </div>
                </template>
                </template>
            </div>
            <template x-if="currencies.length > 1">
            <div class="col-lg-8 offset-lg-2 col-md-12 col-sm-12 col-xs-12 mb-2">
                <template x-for="currency in currencies" :key="currency.id">
                <div class="card mb-2">
                    <div class="card-header">
                        <h3 class="card-title" x-text="currency.name"></h3>X
                    </div>
                    <div class="card-body">
                        <template x-if="currencies.length > 1">
                        <ul>
                            <template x-for="sub in currencies" :key="sub.id">
                                <template x-if="parseInt(sub.id) !== parseInt(currency.id)">
                                    <li>
                                        <a :href="'exchange-rates/' + currency.code + '/' + sub.code"
                                           :title="i18next.t('firefly.exchange_rates_from_to', {from: currency.name, to: sub.name})"
                                           x-text="i18next.t('firefly.exchange_rates_from_to', {from: currency.name, to: sub.name})"></a>
                                    </li>
                                </template>
                                {{--
                                <template x-show="sub.id !== currency.id">
                                    <li>
                                        <a :href="'exchange-rates/' + currency.code + '/' + sub.code"
                                           :title="i18next.t('firefly.exchange_rates_from_to', {from: currency.name, to: sub.name})"
                                           x-text="i18next.t('firefly.exchange_rates_from_to', {from: currency.name, to: sub.name})"></a>
                                    </li>
                                </template>
                                --}}
                            </template>
                        </ul>
                        </template>
                    </div>
                </div>
                </template>
            </div>
        </template>
        </div>
    </div>
@endsection
@section('scripts')
    @vite(['js/pages/exchange-rates/index.js'])
@endsection
