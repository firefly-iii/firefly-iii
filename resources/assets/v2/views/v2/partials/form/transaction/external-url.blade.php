@if(true === $optionalFields['external_url'])
<div class="row mb-3">
    <label :for="'external_url_' + index"
           class="col-sm-1 col-form-label d-none d-sm-block">
        <em title="{{ __('firefly.external_url') }}" class="fa-solid fa-link"></em>
    </label>
    <div class="col-sm-10">
        <input type="text"
               class="form-control"
               :id="'external_url_' + index"
               x-model="transaction.external_url"
               :data-index="index"
               :class="{'is-invalid': transaction.errors.external_url.length > 0, 'form-control': true}"
               placeholder="{{ __('firefly.external_url')  }}" />
        <template x-if="transaction.errors.external_url.length > 0">
            <div class="invalid-feedback"
                 x-text="transaction.errors.external_url[0]">

            </div>
        </template>
    </div>
</div>
@endif
