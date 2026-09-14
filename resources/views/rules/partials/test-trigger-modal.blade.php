<!-- Modal -->
<div class="modal fade" id="testTriggerModal" tabindex="-1" aria-labelledby="testTriggerLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="testTriggerLabel">Modal title</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div class="transaction-warning alert alert-warning">
                    <h4><span class="icon bi bi-exclamation-triangle"></span> {{ __('firefly.flash_warning') }}</h4>
                    <span class="warning-contents"></span>
                </div>
                <div class="transactions-list">
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                <button type="button" class="btn btn-primary">Save changes</button>
            </div>
        </div>
    </div>
</div>

