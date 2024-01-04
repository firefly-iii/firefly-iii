<div class="row mb-3">
    <label :for="'dest_' + index"
           class="col-sm-1 col-form-label d-none d-sm-block">
        <em title="{{ __('firefly.destination_account') }}" class="fa-solid fa-arrow-left"></em>
    </label>
    <div class="col-sm-10">
        <input type="text"
               class="form-control ac-dest"
               :id="'dest_' + index"
               @changed="changedDestinationAccount"
               x-model="transaction.destination_account.alpine_name"
               :data-index="index"
               placeholder="{{ __('firefly.destination_account')  }}">
    </div>
</div>
