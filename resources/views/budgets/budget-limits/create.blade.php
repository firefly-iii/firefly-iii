<div class="modal-dialog">
    <div class="modal-content">
        <div class="modal-header">
            <h5 class="modal-title">
                {{ trans('firefly.set_budget_limit_title',
                    ['start' => $start->isoFormat($monthAndDayFormat), 'end' => $end->isoFormat($monthAndDayFormat), 'budget' => $budget->name]) }}
            </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="{{ __('firefly.close') }}"></button>
        </div>

        <form class="inline" id="income" action="{{ route('budget-limits.store') }}" method="POST">
            <div class="modal-body">
                <input type="hidden" name="_token" value="{{ csrf_token() }}"/>
                <input type="hidden" name="start" value="{{ $start->format('Y-m-d') }}"/>
                <input type="hidden" name="end" value="{{ $end->format('Y-m-d') }}"/>
                <input type="hidden" name="budget_id" value="{{ $budget->id }}"/>
                <div class="form-group mb-3">
                    <select class="form-control" name="transaction_currency_id">
                        @foreach($currencies as $currency)
                            <option label="{{ $currency->name }}" value="{{ $currency->id }}">{{ $currency->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="form-group mb-3">
                    <input step="any" class="form-control" id="amount" value="" autocomplete="off" name="amount" type="number"/>
                </div>
                <div class="form-group mb-3">
                    <textarea name="notes" class="form-control" rows="3" placeholder="{{ __('firefly.notes') }}"></textarea>
                    <span class="help-block">{!! trans('firefly.field_supports_markdown')!!} </span>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">{{ __('firefly.close') }}</button>
                <button type="submit" class="btn btn-primary">{{ __('firefly.set_budget_limit') }}</button>
            </div>
        </form>
    </div>
</div>

