@extends('layout.v3.session')
@section('content')
    <form method="POST" action="{{ route('piggy-banks.add', $piggyBank->id) }}" accept-charset="UTF-8" class="form-horizontal" id="store">
        <input name="_token" type="hidden" value="{{ csrf_token() }}">
        <div class="row">
            <div class="col-lg-12 col-md-12 col-sm-12">
                <div class="card mb-2">
                    <div class="card-header">
                        <h3 class="card-title">{{ trans('firefly.add_money_to_piggy', ['name' => $piggyBank->name]) }}</h3>
                    </div>
                    <div class="card-body">
                        @if($total > 0)
                            @foreach($accounts as $account)
                                <div class="mb-3">
                                    <label for="basic-url" class="form-label">{{ $account['account']->name }} ({{ __('firefly.max_amount_add') }}: {!! format_amount_by_currency($piggyBank->transactionCurrency, $account['max_amount']) !!})</label>
                                    <div class="input-group">
                                        <span class="input-group-text" id="basic-addon3">{{ $piggyBank->transactionCurrency->symbol }}</span>
                                        <input step="any" min="0" class="form-control" id="amount_{{ $account['account']->id }}" autocomplete="off" name="amount[{{ $account['account']->id }}]" max="{{ round($account['max_amount'], $piggyBank->transactionCurrency->decimal_places) }}" type="number"/>
                                    </div>
                                </div>
                            @endforeach
                            <button type="submit" class="btn btn-success text-end">
                                {{ __('firefly.add') }}
                            </button>
                        @else
                            <p class="text-danger">{{ __('firefly.no_money_for_piggy') }}</p>
                        @endif
                    </div>
                </div>
            </div>

        </div>
    </form>
@endsection
@section('scripts')
    @vite(['js/pages/generic.js'])
@endsection
