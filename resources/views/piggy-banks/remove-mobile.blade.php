@extends('layout.v3.session')
@section('content')
    <form id="remove" class="form-horizontal" action="{{ route('piggy-banks.remove', $piggyBank->id) }}" method="POST">
        <input type="hidden" name="_token" value="{{ csrf_token() }}"/>
        <div class="row">
            <div class="col-lg-12 col-md-12 col-sm-12">
                <div class="card">
                    <div class="card-header">
                        <h3 class="card-title">{{ trans('firefly.remove_money_from_piggy_title', ['name' => $piggyBank->name]) }}</h3>
                    </div>
                    <div class="card-body">

                        @foreach($accounts as $account)

                            <div class="mb-3">
                                <label for="basic-url" class="form-label">{{ $account['account']->name }} ({{ __('firefly.max_amount_remove') }}: {!! format_amount_by_currency($piggyBank->transactionCurrency, $account['saved_so_far']) !!})</label>
                                <div class="input-group">
                                    <span class="input-group-text" id="basic-addon3">{{ $piggyBank->transactionCurrency->symbol }}</span>
                                    <input step="any" class="form-control" id="amount_{{ $account['account']->id }}" autocomplete="off" name="amount[{{ $account['account']->id }}]" max="{{ $account['saved_so_far'] }}" type="number">
                                </div>
                            </div>
                        @endforeach
                        <button type="submit" class="btn btn-success text-end">
                            {{ __('firefly.remove') }}
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </form>
@endsection
@section('scripts')
    @vite(['js/pages/generic.js'])
@endsection
