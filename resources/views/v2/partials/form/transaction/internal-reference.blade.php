@if(true === $optionalFields['internal_reference'])
<div class="row mb-3">
    <label :for="'internal_reference_' + index"
           class="col-sm-1 col-form-label d-none d-sm-block">
        <em title="{{ __('firefly.internal_reference') }}" class="fa-solid fa-anchor"></em>
    </label>
    <div class="col-sm-10">
        <input type="search"
               class="form-control"
               :id="'internal_reference_' + index"
               x-model="transaction.internal_reference"
               :data-index="index"
               placeholder="{{ __('firefly.internal_reference')  }}">
    </div>
</div>
@endif
