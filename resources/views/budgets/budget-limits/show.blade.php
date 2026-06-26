<div class="modal-dialog">
    <div class="modal-content">
        <div class="modal-header">
            <h5 class="modal-title">
                {{ trans('firefly.set_budget_limit_notes') }}
            </h5>
            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="{{ __('firefly.close') }}"></button>
        </div>
            <div class="modal-body">
                <div>
                    {!! parse_markdown($notes) !!}
                </div>
            </div>
        <div class="modal-footer">
            <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">{{ __('firefly.close') }}</button>
        </div>
    </div>
</div>

