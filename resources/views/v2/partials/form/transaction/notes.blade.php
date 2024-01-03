<div class="row mb-3">
    <label :for="'notes_' + index"
           class="col-sm-1 col-form-label d-none d-sm-block">
        <i class="fa-solid fa-font"></i>
    </label>
    <div class="col-sm-10">
                                                <textarea class="form-control" :id="'notes_' + index"
                                                          x-model="transaction.notes"
                                                          placeholder="{{ __('firefly.notes')  }}"></textarea>
    </div>
</div>
