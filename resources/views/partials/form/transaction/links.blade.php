<template x-if="true === formBehaviour.customFields.links">
<div class="row mb-3">
    <label :for="'category_name_' + index" class="col-sm-1 col-form-label d-none d-sm-block">
        <em title="{{ __('firefly.journal_links') }}" class="bi bi-link"></em>
    </label>
    <div class="col-sm-10">
        <div class="form-group">

        <div class="form-control-plaintext">
            <template x-if="formStates.loadingLinks">
                <button disabled="disabled" class="btn btn-sm btn-outline-primary" type="button">
                    <div class="spinner-border spinner-border-sm" role="status">
                        <span class="visually-hidden">{{ __('firefly.thinking') }}</span>
                    </div>
                </button>
            </template>
            <template x-if="!formStates.loadingLinks">
                <button class="btn btn-sm btn-outline-primary" data-bs-toggle="modal" :data-bs-target="'#links_modal_' + index" type="button">
                    {{ __('firefly.manage_related_transactions') }}
                    </button>
            </template>
        </div>
        </div>
    </div>
</div>





</template>
