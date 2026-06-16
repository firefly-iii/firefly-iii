@extends('layout.v3.session')
@section('content')
    @if(0 === count($piggyBanks))
        <x-empty-page :route="route('piggy-banks.create')" type="piggies" object-type="default" />
    @endif
    @if(count($piggyBanks) > 0)
        <div class="row">
            <div class="col-lg-12 col-md-12 col-sm-12">
                <div class="card mb-3">
                    <x-elements.card-header-with-menu :cardTitle="__('firefly.piggyBanks')" :route="route('piggy-banks.create')" :linkTitle="__('firefly.create_new_piggy_bank')" />
                    <div class="card-body p-0">
                        <x-lists.piggy-banks :piggyBanks="$piggyBanks" />
                    </div>
                    <x-elements.card-footer-with-menu :route="route('piggy-banks.create')" :linkTitle="__('firefly.create_new_piggy_bank')" />
                </div>
            </div>
        </div>

        <div class="row">
            <div class="col-lg-12 col-md-12 col-sm-12">
                <div class="card mb-3">
                    <div class="card-header">
                        <h3 class="card-title">{{ __('firefly.account_status') }}</h3>
                    </div>
                    <div class="card-body p-0">
                        <table class="table table-hover" id="accountStatus">
                            <thead>
                            <tr>
                                <th>{{ __('firefly.account') }}</th>
                                <th class="text-right hidden-sm hidden-xs">{{ __('firefly.balance') }}</th>
                                <th class="text-right">{{ __('firefly.left_for_piggy_banks') }}</th>
                                <th class="text-right hidden-sm hidden-xs">{{ __('firefly.sum_of_piggy_banks') }}</th>
                                <th class="text-right hidden-sm hidden-xs">{{ __('firefly.saved_so_far') }}</th>
                                <th class="text-right hidden-sm hidden-xs">{{ __('firefly.left_to_save') }}</th>
                            </tr>
                            </thead>
                            <tbody>
                            @foreach($accounts as $id => $info)
                                <tr>
                                    <td><a href="{{ route('accounts.show', $id) }}" title="{{ $info['name'] }}">{{ $info['name'] }}</a></td>
                                    <td class="text-right hidden-sm hidden-xs">
                                        {!! \FireflyIII\Support\Facades\Steam::formatAmountBySymbol($info['current_balance'],$info['currency_symbol'],$info['currency_decimal_places']) !!}
                                    </td>
                                    <td class="text-right">
                                        {!! \FireflyIII\Support\Facades\Steam::formatAmountBySymbol($info['left'],$info['currency_symbol'],$info['currency_decimal_places']) !!}
                                    </td>
                                    <td class="text-right hidden-sm hidden-xs">
                                        {!! \FireflyIII\Support\Facades\Steam::formatAmountBySymbol($info['target'],$info['currency_symbol'],$info['currency_decimal_places']) !!}
                                    </td>
                                    <td class="text-right hidden-sm hidden-xs">
                                        {!! \FireflyIII\Support\Facades\Steam::formatAmountBySymbol($info['saved'],$info['currency_symbol'],$info['currency_decimal_places']) !!}
                                    </td>
                                    <td class="text-right hidden-sm hidden-xs">
                                        {!! \FireflyIII\Support\Facades\Steam::formatAmountBySymbol($info['to_save'],$info['currency_symbol'],$info['currency_decimal_places']) !!}
                                    </td>
                                </tr>
                            @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    @endif
@endsection
@section('scripts')
    @vite(['js/pages/piggy-banks/index.js'])
    <script src="v1/js/lib/jquery-ui.min.js?v={{ $FF_BUILD_TIME }}" type="text/javascript" nonce="{{ $JS_NONCE }}"></script>
    <script type="text/javascript" src="v1/js/ff/piggy-banks/index.js?v={{ $FF_BUILD_TIME }}" nonce="{{ $JS_NONCE }}"></script>
@endsection
