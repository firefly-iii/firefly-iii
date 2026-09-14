<div class="modal-dialog">
    <div class="modal-content">
        <div class="modal-header">
            <h5 class="modal-title">{{ trans('firefly.add_money_to_piggy_title', ['name' => $piggyBank->name]) }}</h5>
            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="{{ __('firefly.close') }}"></button>
        </div>
        @if($total > 0)
            <form class="inline" id="add" action="{{ route('piggy-banks.add', $piggyBank->id) }}" method="POST">
                <div class="modal-body">
                    <input type="hidden" name="_token" value="{{ csrf_token() }}"/>
                    @foreach($accounts as $account)
                        <div class="mb-3">
                            <label for="basic-url" class="form-label">{{ $account['account']->name }} ({{ __('firefly.max_amount_add') }}: {!! format_amount_by_currency($piggyBank->transactionCurrency, $account['max_amount']) !!})</label>
                            <div class="input-group">
                                <span class="input-group-text" id="basic-addon3">{{ $piggyBank->transactionCurrency->symbol }}</span>
                                <input step="any" min="0" class="form-control" id="amount_{{ $account['account']->id }}" autocomplete="off" name="amount[{{ $account['account']->id }}]" max="{{ round($account['max_amount'], $piggyBank->transactionCurrency->decimal_places) }}" type="number"/>
                            </div>
                        </div>
                    @endforeach
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">{{ __('firefly.close') }}</button>
                    <button type="submit" class="btn btn-primary">{{ __('firefly.add') }}</button>
                </div>
            </form>
        @else
            <div class="modal-body">
                <p class="text-danger">{{ __('firefly.no_money_for_piggy') }}</p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">{{ __('firefly.close') }}</button>
            </div>
        @endif
    </div>
</div>

