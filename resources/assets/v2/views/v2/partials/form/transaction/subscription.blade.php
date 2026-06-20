<template x-if="groupProperties.transactionType != 'transfer' && groupProperties.transactionType != 'deposit'">
    <div class="row mb-3">
        <label :for="'bill_id_' + index" class="col-sm-1 col-form-label d-none d-sm-block">
            <em title="{{ __('firefly.subscription') }}" class="fa-solid fa-calendar"></em>
        </label>
        <div class="col-sm-10">
            <template x-if="formStates.loadingSubscriptions">
                <span class="form-control-plaintext"><em class="fa-solid fa-spinner fa-spin"></em></span>
            </template>
            <template x-if="!formStates.loadingSubscriptions">
                <select class="form-control"
                        :id="'bill_id_' + index"
                        @keyup.enter="submitTransaction()"
                        x-model="transaction.bill_id">
                    <template x-for="group in formData.subscriptions">
                        <optgroup :label="group.name">
                            <template
                                x-for="subscription in group.subscriptions">
                                <option :label="subscription.name"
                                        :value="subscription.id"
                                        :selected="subscription.id == transaction.bill_id"
                                        x-text="subscription.name"></option>
                            </template>
                        </optgroup>
                    </template>
                </select>
            </template>
        </div>
    </div>
</template>
