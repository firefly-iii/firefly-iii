<template x-if="groupProperties.transactionType != 'deposit' && groupProperties.transactionType != 'transfer'">
    <div class="row mb-3">
        <label :for="'budget_id_' + index"
               class="col-sm-1 col-form-label d-none d-sm-block">
            <em title="{{ __('firefly.budget') }}" class="fa-solid fa-chart-pie"></em>
        </label>
        <div class="col-sm-10">
            <template x-if="formStates.loadingBudgets">
                <span class="form-control-plaintext"><em class="fa-solid fa-spinner fa-spin"></em></span>
            </template>
            <template x-if="!formStates.loadingBudgets">
                <select class="form-control"
                        :id="'budget_id_' + index"
                        x-model="transaction.budget_id"
                        @keyup.enter="submitTransaction()"
                >
                    <template x-for="budget in formData.budgets">
                        <option :label="budget.name" :value="budget.id" :selected="budget.id == transaction.budget_id"
                                x-text="budget.name"></option>
                    </template>
                </select>
            </template>
        </div>
    </div>
</template>
