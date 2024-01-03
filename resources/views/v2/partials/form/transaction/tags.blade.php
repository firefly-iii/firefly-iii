<div class="row mb-3">
    <label :for="'tags_' + index"
           class="col-sm-1 col-form-label d-none d-sm-block">
        <i class="fa-solid fa-tag"></i>
    </label>
    <div class="col-sm-10">
        <select
            class="form-select ac-tags"
            :id="'tags_' + index"
            x-model="transaction.tags"
            :name="'tags['+index+'][]'"
            multiple>
            <option value="">Type a tag...</option>
        </select>
    </div>
</div>
