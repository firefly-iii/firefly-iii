<div class="row mb-3">
    <label for="title" class="col-sm-1 col-form-label d-none d-sm-block">
        <em title="{{ __('firefly.title') }}" class="fa-solid fa-font"></em>
    </label>
    <div class="col-sm-10">
        <input type="text" class="form-control ac-title"
               id="title"
               @change="changedTitle"
               @keyup.enter="submitForm()"
               x-model="title"
               :class="{'is-invalid': errors.title.length > 0, 'form-control': true}"
               placeholder="{{ __('form.title')  }}">
        <template x-if="errors.title.length > 0">
            <div class="invalid-feedback"
                 x-text="errors.title[0]">
            </div>
        </template>
    </div>
</div>
