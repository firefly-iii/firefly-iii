<div class="row mb-3">
    <label :for="'description_' + index" class="col-sm-1 col-form-label d-none d-sm-block">
        <em title="{{ __('firefly.description') }}" class="fa-solid fa-font"></em>
    </label>
    <div class="col-sm-10">
        <input type="text" class="form-control ac-description"
               :id="'description_' + index"
               @change="changedDescription"
               @keyup.enter="submitTransaction()"
               x-model="transaction.description"
               :class="{'is-invalid': transaction.errors.description.length > 0, 'form-control': true}"
               :data-index="index"
               placeholder="{{ __('firefly.description')  }}">
        <template x-if="transaction.errors.description.length > 0">
            <div class="invalid-feedback"
                 x-text="transaction.errors.description[0]">
            </div>
        </template>
    </div>
</div>
