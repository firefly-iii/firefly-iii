<template x-if="form.deliveries.loading">
    <div class="row mb-3 text-center">
            <span class="form-control-plaintext"><div class="spinner-border spinner-border-sm" role="status">
                <span class="visually-hidden">Loading...</span>
            </div></span>
    </div>
</template>
<template x-if="!form.deliveries.loading">
    <div class="row mb-3">
        <label for="form_deliveries" class="col-sm-3 col-form-label has-validation">{{ __('form.deliveries') }}</label>
        <div class="col-sm-9">
            <select
                id="form_deliveries"
                ref="deliveries"
                x-model={{ $value }}
                class="form-select"
                name="deliveries[]"
                @input="handleInput"
            >
                <template x-for="delivery in options.deliveries">
                    <option :label="delivery.name" :selected="deliveries.includes(delivery.id)" :value="delivery.id" x-text="delivery.name"></option>
                </template>
            </select>
        </div>
    </div>
    <template x-for="error in errors.deliveries">
        <ul class="list-unstyled">
            <li class="text-danger" x-text="error"></li>
        </ul>
    </template>
</template>
