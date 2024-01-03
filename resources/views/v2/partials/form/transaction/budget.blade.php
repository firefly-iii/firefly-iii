<template x-if="transactionType != 'deposit' && transactionType != 'transfer'">
    <div class="row mb-3">
        <label :for="'budget_id_' + index"
               class="col-sm-1 col-form-label d-none d-sm-block">
            <i class="fa-solid fa-chart-pie"></i>
        </label>
        <div class="col-sm-10">
            <template x-if="loadingBudgets">
                                                        <span class="form-control-plaintext"><em
                                                                class="fa-solid fa-spinner fa-spin"></em></span>
            </template>
            <template x-if="!loadingBudgets">
                <select class="form-control"
                        :id="'budget_id_' + index"
                        x-model="transaction.budget_id"
                >
                    <template x-for="budget in budgets">
                        <option :label="budget.name" :value="budget.id"
                                x-text="budget.name"></option>
                    </template>
                </select>
            </template>
        </div>
    </div>
</template>
