<template x-if="formBehaviour.foreignCurrencyEnabled">
    <div class="row mb-3">
        <div class="col-sm-3">
            <label class="form-label">&nbsp;</label>
            <template x-if="formStates.loadingCurrencies">
                <span class="form-control-plaintext"><em class="fa-solid fa-spinner fa-spin"></em></span>
            </template>
            <template x-if="!formStates.loadingCurrencies">
                <select class="form-control"
                        :id="'foreign_currency_code_' + index"
                        x-model="transaction.foreign_currency_code">
                    <template x-for="currency in formData.foreignCurrencies">
                        <option :label="currency.name" :value="currency.code"
                                x-text="currency.name"></option>
                    </template>
                </select>
            </template>
        </div>
        <div class="col-sm-9">
            <template x-if="groupProperties.transactionType != 'transfer'">
                <label class="small form-label">{{ __('firefly.amount_foreign_if') }}</label>
            </template>
            <template x-if="groupProperties.transactionType == 'transfer'">
                <label class="small form-label">{{ __('firefly.amount_destination_account') }}</label>
            </template>
            <input type="number" step="any" min="0"
                   :id="'amount_' + index"
                   :data-index="index"
                   :class="{'is-invalid': transaction.errors.foreign_amount.length > 0, 'input-mask' : true, 'form-control': true}"
                   x-model="transaction.foreign_amount"
                   @change="changedAmount"
                   placeholder="0.00">
            <template x-if="transaction.errors.foreign_amount.length > 0">
                <div class="invalid-feedback" x-text="transaction.errors.foreign_amount[0]"></div>
            </template>
        </div>
    </div>
</template>
