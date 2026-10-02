<div class="row mb-2">
    <!-- text label for currency -->
    <label :for="'amount_' + index"
           class="col-sm-3 col-form-label d-none d-sm-block" x-text="formData.amountCurrency ? formData.amountCurrency.name : ''">
    </label>
    <!-- actual amount -->
    <div class="col-sm-9">
        <div class="input-group">
        <input type="number" step="any" min="0"
               :id="'amount_' + index"
               :data-index="index"
               x-bind:disabled="transaction.reconciled"
               x-bind:readonly="transaction.reconciled"
               :class="{'is-invalid': transaction.errors.amount.length > 0, 'input-mask' : true, 'form-control': true}"
               x-model="transaction.amount"
               @keyup.enter="save()"
               @change="changedAmount"
               placeholder="0.00">
            <button tabindex="-1" class="btn btn-outline-secondary" type="button" @click="clearAmount(index)"><em class="bi bi-trash"></em></button>
        </div>
        <template x-if="transaction.errors.amount.length > 0">
            <div class="invalid-feedback"
                 x-text="transaction.errors.amount[0]"></div>
        </template>
    </div>
</div>
