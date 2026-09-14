<!-- RETURN HERE AFTER CREATE TRANSACTION -->
<template x-if="'create' === formBehaviour.formType">
    <div class="form-check">
        <input class="form-check-input" x-model="formStates.returnHereButton" type="checkbox" id="returnButton">
        <label class="form-check-label" for="returnButton">{{ __('firefly.create_another') }}</label>
    </div>
</template>

<!-- RESET AFTER -->
<template x-if="'create' === formBehaviour.formType">
    <div class="form-check">
        <input class="form-check-input" x-model="formStates.resetButton" type="checkbox" id="resetButton" :disabled="!formStates.returnHereButton">
        <label class="form-check-label" for="resetButton">{{ __('firefly.reset_after') }}</label>
    </div>
</template>
<!-- RETURN HERE AFTER EDIT TRANSACTION -->
<template x-if="'edit' === formBehaviour.formType">
    <div class="form-check">
        <input class="form-check-input" x-model="formStates.returnHereButton" type="checkbox" id="returnButton">
        <label class="form-check-label" for="returnButton">{{ __('firefly.after_update_create_another') }}</label>
    </div>
</template>
<!-- CLONE INSTEAD OF EDIT CURRENT TRANSACTION -->
<template x-if="'edit' === formBehaviour.formType">
    <div class="form-check">
        <input class="form-check-input" x-model="formStates.saveAsNewButton" type="checkbox" id="saveAsNewButton">
        <label class="form-check-label" for="saveAsNewButton">{{ __('firefly.store_as_new') }}</label>
    </div>
</template>

<div class="form-check">
    <input class="form-check-input" type="checkbox" id="rulesButton" :checked="formStates.rulesButton">
    <label class="form-check-label" for="rulesButton">{{ __('firefly.apply_rules_checkbox') }}</label>
</div>

<div class="form-check">
    <input class="form-check-input" type="checkbox" id="webhooksButton" :checked="formStates.webhooksButton">
    <label class="form-check-label" for="webhooksButton">{{ __('firefly.fire_webhooks_checkbox') }}</label>
</div>
