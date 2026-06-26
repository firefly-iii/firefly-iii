<div class="modal fade" id="testTriggerModal" tabindex="-1" role="dialog" aria-labelledby="testTriggerLabel">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="testTriggerLabel">{{ 'test_rule_triggers'|_ }}</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="{{ __('firefly.close') }}"></button>
            </div>
            <div class="modal-body">
                <div class="transaction-warning alert alert-warning">
                    <h4><span class="icon bi bi-exclamation-triangle"></span> {{ 'flash_warning'|_ }}</h4>
                    <span class="warning-contents"></span>
                </div>
                <div class="transactions-list">
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">{{ __('firefly.close') }}</button>
            </div>
        </div>
    </div>
</div>
