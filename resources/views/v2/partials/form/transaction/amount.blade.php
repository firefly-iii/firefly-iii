<div class="row mb-3">
    <div class="col-sm-3">
        <template x-if="loadingCurrencies">
                                                    <span class="form-control-plaintext"><em
                                                            class="fa-solid fa-spinner fa-spin"></em></span>
        </template>
        <template x-if="!loadingCurrencies">
            <select class="form-control" :id="'currency_code_' + index"
                    x-model="transaction.currency_code"
            >
                <template x-for="currency in nativeCurrencies">
                    <option :selected="currency.id == defaultCurrency.id"
                            :label="currency.name" :value="currency.code"
                            x-text="currency.name"></option>
                </template>
            </select>
        </template>
    </div>
    <div class="col-sm-9">
        <input type="number" step="any" min="0"
               :id="'amount_' + index"
               :data-index="index"
               :class="{'is-invalid': transaction.errors.amount.length > 0, 'input-mask' : true, 'form-control': true}"
               x-model="transaction.amount" data-inputmask="currency"
               @change="changedAmount"
               placeholder="0.00">
        <template x-if="transaction.errors.amount.length > 0">
            <div class="invalid-feedback"
                 x-text="transaction.errors.amount[0]"></div>
        </template>
    </div>
</div>
