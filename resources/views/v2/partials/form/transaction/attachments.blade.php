<div class="row mb-3">
    <label :for="'attachments_' + index"
           class="col-sm-1 col-form-label d-none d-sm-block">
        <i class="fa-solid fa-file-import"></i>
    </label>
    <div class="col-sm-10">
        <input type="file" multiple
               class="form-control attachments"
               :id="'attachments_' + index"
               :data-index="index"
               name="attachments[]"
               placeholder="{{ __('firefly.category')  }}">
    </div>
</div>
