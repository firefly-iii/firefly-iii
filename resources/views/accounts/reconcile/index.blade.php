@extends('layout.v3.session')
@section('content')
    <div class="row">
        <div class="col-lg-9 col-md-12 col-sm-12 col-xs-12">
            <div class="card mb-2">
                <div class="card-header">
                    <h3 class="card-title">{{ __('firefly.reconcile_range') }}</h3>
                </div>
                <div class="card-body p-0">
                    <table class="table table-hover">
                        <thead>
                        <tr>
                            <th colspan="2" class="half">{{ __('firefly.start_balance') }}</th>
                            <th colspan="2">{{ __('firefly.end_balance') }}</th>
                        </tr>
                        </thead>
                        <tbody>
                        <tr>
                            <td class="quarter">
                                {{ __('firefly.date') }}
                            </td>
                            <td class="quarter">
                                {{ __('firefly.balance') }}
                            </td>
                            <td class="quarter">
                                {{ __('firefly.date') }}
                            </td>
                            <td class="quarter">
                                {{ __('firefly.balance') }}
                            </td>
                        </tr>
                        <tr>
                            <td>
                                <div class="input-group">
                                    <span class="input-group-text">
                                        <span class="bi bi-calendar"></span>
                                    </span>
                                    <input type="date" value="{{ $start->format('Y-m-d') }}" name="start_date" class="form-control" spellcheck="false">
                                </div>
                            </td>
                            <td>
                                <div class="input-group">
                                    <span class="input-group-text non-selectable-currency-symbol">{{ $currency->symbol }}</span>
                                    <input type="number" value="{{ $startBalance }}" name="start_balance" class="form-control" spellcheck="false">
                                </div>
                            </td>
                            <td>
                                <div class="input-group">
                                    <span class="input-group-text">
                                        <span class="bi bi-calendar"></span>
                                    </span>
                                    <input type="date" value="{{ $end->format('Y-m-d') }}" name="end_date" class="form-control" spellcheck="false">
                                </div>
                            </td>
                            <td>
                                <div class="input-group">
                                    <span class="input-group-text non-selectable-currency-symbol">{{ $currency->symbol }}</span>
                                    <input type="number" value="{{ $endBalance }}" name="end_balance" class="form-control" spellcheck="false">
                                </div>
                            </td>
                        </tr>
                        </tbody>
                        <tfoot>
                        <tr>
                            <td colspan="3">
                                <div class="update_balance_instruction">
                                    {{ __('firefly.update_balance_dates_instruction') }}
                                </div>
                                <div class="select_transactions_instruction d-none">
                                    {{ __('firefly.select_transactions_instruction') }}
                                </div>
                                <div class="date_change_warning text-danger d-none">
                                    {{ __('firefly.date_change_instruction') }}
                                </div>
                            </td>
                            <td>
                                <a href="#" class="btn btn-outline-secondary start_reconcile">{{ __('firefly.start_reconcile') }}</a>
                                <a href="#" class="btn btn-outline-secondary change_date_button d-none">{{ __('firefly.update_selection') }}</a>
                            </td>
                        </tr>
                        </tfoot>
                    </table>
                </div>
            </div>
        </div>
        <div class="col-lg-3 col-md-12 col-sm-12 col-xs-12">
            <div class="card mb-2">
                <div class="card-header">
                    <h3 class="card-title">{{ __('firefly.reconcile_options') }}</h3>
                </div>
                <div class="card-body">
                    <p class="lead" id="difference"></p>

                    <div class="btn-group">
                        <button class="btn btn-outline-secondary store_reconcile" disabled><span class="bi bi-check"></span> {{ __('firefly.store_reconcile') }}</button>
                    </div>
                </div>
            </div>
        </div>

    </div>

    <div class="row">
        <div class="col-lg-12 col-md-12 col-sm-12">
            <div class="card mb-2">
                <div class="card-header">
                    <h3 class="card-title">{{ __('firefly.transactions') }}</h3>
                </div>
                <div class="card-body">
                    <div id="transactions_holder">
                        <p class="text-center lead">{{ __('firefly.select_range_and_balance') }}</p>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection

@section('scripts')
    @vite(['js/pages/generic.js'])

    <script type="text/javascript" nonce="{{ $JS_NONCE }}">
        currencySymbol = "{{ $currency->symbol }}";
        var accountID = {{ $account->id }};
        var startBalance = {{ $startBalance }};
        var endBalance = {{ $endBalance }};
        var transactionsUrl = '{{ $transactionsUrl }}';
        var overviewUrl = '{{ $overviewUrl }}';
        var indexUrl = '{{ $indexUrl }}';
        var selectRangeAndBalance = '{{ __('firefly.select_range_and_balance') }}';
    </script>
    <script src="v1/js/ff/accounts/reconcile.js?v={{ $FF_BUILD_TIME }}" type="text/javascript" nonce="{{ $JS_NONCE }}"></script>
@endsection
