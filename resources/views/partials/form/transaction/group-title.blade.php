<template x-if="entries.length > 1">
<div class="row mb-3">
    <label for="group-title" class="col-sm-1 col-form-label d-none d-sm-block">
        <em title="{{ __('firefly.group-title') }}" class="bi bi-body-text"></em>
    </label>
    <div class="col-sm-10">
        <div class="input-group">
        <input type="text" class="form-control ac-group-title"
               id="group-title"
               @change="changedGroupTitle"
               @keyup.enter="submitTransaction()"
               x-model="groupProperties.title"
               :disabled="index > 0"
               :class="{'is-invalid': groupProperties.titleErrors.length > 0, 'form-control': true}"
               placeholder="{{ __('firefly.group-title')  }}">
            <button :disabled="index > 0" tabindex="-1" class="btn btn-outline-secondary" type="button" @click="cleargroupTitle()"><em class="bi bi-trash"></em></button>
        </div>
        <template x-if="groupProperties.titleErrors.length > 0">
            <div class="invalid-feedback"
                 x-text="groupProperties.titleErrors[0]">
            </div>
        </template>
    </div>
</div>
</template>
