<div class="row mb-3">
    <label :for="'tags_' + index"
           class="col-sm-1 col-form-label d-none d-sm-block">
        <em title="{{ __('firefly.tags') }}" class="fa-solid fa-tag"></em>
    </label>
    <div class="col-sm-10">
        <select
            class="form-select ac-tags"
            :id="'tags_' + index"
            x-model="transaction.tags"
            :name="'tags['+index+'][]'"
            multiple>
            <option value="">{{ __('firefly.select_tag') }}</option>
        </select>
    </div>
</div>
