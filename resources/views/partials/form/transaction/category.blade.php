<div class="row mb-3">
    <label :for="'category_name_' + index" class="col-sm-1 col-form-label d-none d-sm-block">
        <em title="{{ __('firefly.category') }}" class="bi bi-bookmark"></em>
    </label>
    <div class="col-sm-10">
        <div class="input-group">
        <input type="search"
               class="form-control ac-category"
               :id="'category_name_' + index"
               @keyup="keyUpFromCategory"
               x-model="transaction.category_name"
               :data-index="index"
               placeholder="{{ __('firefly.category')  }}">
        <button tabindex="-1" class="btn btn-outline-secondary" type="button" @click="clearCategory(index)"><em class="bi bi-trash"></em></button>
        </div>
    </div>
</div>
