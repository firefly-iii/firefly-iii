<div class="modal-dialog">
    <div class="modal-content">
        <div class="modal-header">
            <h5 class="modal-title">
                {{ trans('firefly.edit_bl_notes') }}
            </h5>
            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="{{ __('firefly.close') }}"></button>
        </div>

        <form class="inline"  action="{{ route('budget-limits.update', [$budgetLimit->id]) }}" method="POST">
            <div class="modal-body">
                <input type="hidden" name="_token" value="{{ csrf_token() }}"/>
                <input type="hidden" name="redirect" value="true"/>
                <input type="hidden" name="amount" value="{{ $budgetLimit->amount }}"/>
                <div class="form-group mb-3">
                    <textarea name="notes" class="form-control" rows="3" placeholder="{{ __('firefly.notes') }}">{{ $notes }}</textarea>
                    <span class="help-block">{!! trans('firefly.field_supports_markdown') !!}</span>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">{{ __('firefly.close') }}</button>
                <button type="submit" class="btn btn-primary">{{ __('firefly.update_bl_notes') }}</button>
            </div>
        </form>
    </div>
</div>

