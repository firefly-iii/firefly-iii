<template x-if="true === formBehaviour.customFields.links">
<div class="row mb-3">
    <label :for="'category_name_' + index" class="col-sm-1 col-form-label d-none d-sm-block">
        <em title="{{ __('firefly.journal_links') }}" class="bi bi-link"></em>
    </label>
    <div class="col-sm-10">
        <div class="form-group">

        <div class="form-control-plaintext">
            <a class="btn btn-sm btn-outline-primary" href="#">Manage related transactions</a>
        </div>
        </div>
    </div>
</div>
</template>
