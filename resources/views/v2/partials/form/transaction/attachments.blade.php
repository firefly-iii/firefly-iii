@if(true === $optionalFields['attachments'])
    <div class="row mb-3">
        <label :for="'attachments_' + index"
               class="col-sm-1 col-form-label d-none d-sm-block">
            <em title="{{ __('firefly.attachments') }}" class="fa-solid fa-file-import"></em>
        </label>
        <div class="col-sm-10">
            <input type="file" multiple
                   class="form-control attachments"
                   :id="'attachments_' + index"
                   :data-index="index"
                   name="attachments[]"
                   >
        </div>
    </div>
@endif
