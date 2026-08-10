<template x-if="'unknown' !== groupProperties.transactionType">
    <div class="row mb-0">
        <label class="col-sm-1 col-form-label d-none d-sm-block">
            &nbsp;
        </label>
        <div class="col-sm-10">
            <em x-text="i18next.t('firefly.you_create_' + groupProperties.transactionType)"></em>
        </div>
    </div>
</template>
