<div class="row mb-3">
    <label :for="'dest_' + index"
           class="col-sm-1 col-form-label d-none d-sm-block">
        <em title="{{ __('firefly.destination_account') }}" class="bi bi-arrow-right"></em>
    </label>
    <div class="col-sm-10">
        <div class="input-group">
        <input type="text"
               :class="{'is-invalid': transaction.errors.destination_account.length > 0, 'form-control': true, 'ac-dest': true}"
               :id="'dest_' + index"
               x-model="transaction.destination_account.alpine_name"
               :data-index="index"
               @changed="changedDestinationAccount"
               x-bind:disabled="true===transaction.destination_account.disabled"
               x-bind:readonly="true===transaction.destination_account.disabled"
               placeholder="{{ __('firefly.destination_account')  }}">
            <button tabindex="-1" class="btn btn-outline-secondary" type="button" @click="clearDestinationAccount(index)"><em class="bi bi-trash"></em></button>
        </div>
        <template x-if="true===transaction.destination_account.disabled">
            <div class="small form-control-feedback">
                {{ __('firefly.disabled_split_account_dest') }}
            </div>
        </template>
        <template x-if="transaction.errors.destination_account.length > 0">
            <div class="invalid-feedback"
                 x-text="transaction.errors.destination_account[0]">
            </div>
        </template>
    </div>
</div>
