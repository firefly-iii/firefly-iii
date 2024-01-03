<template
    x-if="transactionType != 'deposit' && transactionType != 'withdrawal'">
    <div class="row mb-3">
        <label :for="'piggy_bank_id_' + index"
               class="col-sm-1 col-form-label d-none d-sm-block">
            <i class="fa-solid fa-piggy-bank"></i>
        </label>
        <div class="col-sm-10">
            <template x-if="loadingPiggyBanks">
                                                        <span class="form-control-plaintext"><em
                                                                class="fa-solid fa-spinner fa-spin"></em></span>
            </template>
            <template x-if="!loadingPiggyBanks">
                <select class="form-control"
                        :id="'piggy_bank_id_' + index"
                        x-model="transaction.piggy_bank_id">
                    <template x-for="group in piggyBanks">
                        <optgroup :label="group.name">
                            <template x-for="piggyBank in group.piggyBanks">
                                <option :label="piggyBank.name"
                                        :value="piggyBank.id"
                                        x-text="piggyBank.name"></option>
                            </template>
                        </optgroup>
                    </template>
                </select>
            </template>
        </div>
    </div>
</template>
