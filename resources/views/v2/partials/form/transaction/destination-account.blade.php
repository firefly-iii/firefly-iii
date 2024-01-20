<div class="row mb-3">
    <label :for="'dest_' + index"
           class="col-sm-1 col-form-label d-none d-sm-block">
        <em title="{{ __('firefly.destination_account') }}" class="fa-solid fa-arrow-right"></em>
    </label>
    <div class="col-sm-10">
        <input type="text"
               :class="{'is-invalid': transaction.errors.destination_account.length > 0, 'form-control': true, 'ac-dest': true}"
               :id="'dest_' + index"
               x-model="transaction.destination_account.alpine_name"
               :data-index="index"
               @changed="changedDestinationAccount"
               placeholder="{{ __('firefly.destination_account')  }}">
        <template x-if="transaction.errors.destination_account.length > 0">
            <div class="invalid-feedback"
                 x-text="transaction.errors.destination_account[0]">
            </div>
        </template>
    </div>
</div>
