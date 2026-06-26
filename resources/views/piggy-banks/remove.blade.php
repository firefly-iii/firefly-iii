<div class="modal-dialog">
    <div class="modal-content">
        <form class="inline" id="remove" action="{{ route('piggy-banks.remove', $piggyBank->id) }}" method="POST">
            <input type="hidden" name="_token" value="{{ csrf_token() }}"/>

            <div class="modal-header">
                <h5 class="modal-title">{{ trans('firefly.remove_money_from_piggy_title', ['name' => $piggyBank->name]) }}</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="{{ __('firefly.close') }}"></button>
            </div>

            <div class="modal-body">
                @foreach($accounts as $account)

                    <div class="mb-3">
                        <label for="basic-url" class="form-label">{{ $account['account']->name }} ({{ __('firefly.max_amount_remove') }}: {!! format_amount_by_currency($piggyBank->transactionCurrency, $account['saved_so_far']) !!})</label>
                        <div class="input-group">
                            <span class="input-group-text" id="basic-addon3">{{ $piggyBank->transactionCurrency->symbol }}</span>
                            <input step="any" class="form-control" id="amount_{{ $account['account']->id }}" autocomplete="off" name="amount[{{ $account['account']->id }}]" max="{{ $account['saved_so_far'] }}" type="number">
                        </div>
                    </div>
                @endforeach
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">{{ __('firefly.close') }}</button>
                <button type="submit" class="btn btn-primary">{{__( 'firefly.remove') }}</button>
            </div>
        </form>
    </div>

</div>
