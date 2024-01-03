<template x-if="transactionType != 'transfer' && transactionType != 'deposit'">
    <div class="row mb-3">
        <label :for="'bill_id_' + index"
               class="col-sm-1 col-form-label d-none d-sm-block">
            <i class="fa-solid fa-calendar"></i>
        </label>
        <div class="col-sm-10">
            <template x-if="loadingSubscriptions">
                                                        <span class="form-control-plaintext"><em
                                                                class="fa-solid fa-spinner fa-spin"></em></span>
            </template>
            <template x-if="!loadingSubscriptions">
                <select class="form-control"
                        :id="'bill_id_' + index"
                        x-model="transaction.bill_id">
                    <template x-for="group in subscriptions">
                        <optgroup :label="group.name">
                            <template
                                x-for="subscription in group.subscriptions">
                                <option :label="subscription.name"
                                        :value="subscription.id"
                                        x-text="subscription.name"></option>
                            </template>
                        </optgroup>
                    </template>
                </select>
            </template>
        </div>
    </div>
</template>
