<div class="row mb-2">
    <label :for="'tags_' + index"
           class="col-sm-1 col-form-label d-none d-sm-block">
        <em title="{{ __('firefly.tags') }}" class="bi bi-tag"></em>
    </label>
    <div class="col-sm-10">
        <select
            class="form-select ac-tags"
            :id="'tags_' + index"
            :name="'tags['+index+'][]'"
            x-model="transaction.tags"
            multiple>
            <option value="">{{ __('firefly.select_tag') }}</option>

        </select>

    </div>
</div>
