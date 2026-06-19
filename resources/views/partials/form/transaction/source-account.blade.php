<div class="row mb-3">
    <label :for="'source_' + index"
           class="col-sm-1 col-form-label d-none d-sm-block">
        <em title="{{ __('firefly.source_account') }}" class="bi bi-arrow-left"></em>
    </label>
    <div class="col-sm-10">
        <div class="input-group">
        <input type="text"
               :class="{'is-invalid': transaction.errors.source_account.length > 0, 'form-control': true, 'ac-source': true}"
               :id="'source_' + index"
               x-model="transaction.source_account.alpine_name"
               :data-index="index"
               @changed="changedSourceAccount"
               placeholder="{{ __('firefly.source_account')  }}">
            <button tabindex="-1" class="btn btn-outline-secondary" type="button" @click="clearSourceAccount(index)"><em class="bi bi-trash"></em></button>
        </div>
        <template x-if="transaction.errors.source_account.length > 0">
            <div class="invalid-feedback"
                 x-text="transaction.errors.source_account[0]">
            </div>
        </template>
    </div>
</div>
