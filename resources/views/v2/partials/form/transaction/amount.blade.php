<div class="row mb-3">
    <!-- select for currency -->
    <div class="col-sm-3">
        <!-- is loading currencies -->
        <template x-if="formStates.loadingCurrencies">
            <span class="form-control-plaintext"><em class="fa-solid fa-spinner fa-spin"></em></span>
        </template>
        <!-- is no longer loading currencies -->
        <template x-if="!formStates.loadingCurrencies">
            <select class="form-control" :id="'currency_code_' + index" x-model="transaction.currency_code">
                <template x-for="currency in formData.primaryCurrencies">
                    <option :selected="currency.id == formData.primaryCurrency.id"
                            :label="currency.name" :value="currency.code"
                            x-text="currency.name"></option>
                </template>
            </select>
        </template>
    </div>
    <!-- actual amount -->
    <div class="col-sm-9">
        <input type="number" step="any" min="0"
               :id="'amount_' + index"
               :data-index="index"
               :class="{'is-invalid': transaction.errors.amount.length > 0, 'input-mask' : true, 'form-control': true}"
               x-model="transaction.amount"
               @keyup.enter="submitTransaction()"
               @change="changedAmount"
               placeholder="0.00">
        <template x-if="transaction.errors.amount.length > 0">
            <div class="invalid-feedback"
                 x-text="transaction.errors.amount[0]"></div>
        </template>
    </div>
</div>
