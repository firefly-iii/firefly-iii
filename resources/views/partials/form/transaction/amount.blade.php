<div class="row mb-3">
    <!-- text label for currency -->
    <label :for="'amount_' + index"
           class="col-sm-3 col-form-label d-none d-sm-block" x-text="formData.amountCurrency ? formData.amountCurrency.name : ''">
    </label>
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
