<div class="row mb-3">
    <label :for="'description_' + index" class="col-sm-1 col-form-label d-none d-sm-block">
        <em title="TODO explain me" class="fa-solid fa-font"></em>
    </label>
    <div class="col-sm-10">
        <input type="text" class="form-control ac-description"
               :id="'description_' + index"
               @change="detectTransactionType"
               x-model="transaction.description"
               :data-index="index"
               placeholder="{{ __('firefly.description')  }}">
    </div>
</div>
