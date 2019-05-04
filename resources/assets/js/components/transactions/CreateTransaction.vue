<!--
  - CreateTransaction.vue
  - Copyright (c) 2019 thegrumpydictator@gmail.com
  -
  - This file is part of Firefly III.
  -
  - Firefly III is free software: you can redistribute it and/or modify
  - it under the terms of the GNU General Public License as published by
  - the Free Software Foundation, either version 3 of the License, or
  - (at your option) any later version.
  -
  - Firefly III is distributed in the hope that it will be useful,
  - but WITHOUT ANY WARRANTY; without even the implied warranty of
  - MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
  - GNU General Public License for more details.
  -
  - You should have received a copy of the GNU General Public License
  - along with Firefly III. If not, see <http://www.gnu.org/licenses/>.
  -->

<template>
    <form method="POST" action="xxxx" accept-charset="UTF-8" class="form-horizontal" id="store" enctype="multipart/form-data">
        <input name="_token" type="hidden" value="xxx">

        <div class="row" v-if="transactions.transactions.length > 1">
            <div class="col-lg-6">
                <div class="box">
                    <div class="box-header with-border">
                        <h3 class="box-title">
                            Description of the split transaction
                        </h3>
                    </div>
                    <div class="box-body">
                        <div class="form-group">
                            <div class="col-sm-12">
                                <input type="text" class="form-control" name="group_title"
                                       v-model="transactions.group_title"
                                       title="Description of the split transaction" autocomplete="off" placeholder="Description of the split transaction">


                                <p class="help-block">
                                    If you create a split transaction, there must be a global description for all splits of the transaction.
                                </p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div v-for="(transaction, index) in transactions.transactions">
            <div class="row">
                <div class="col-lg-12">
                    <div class="box">
                        <div class="box-header with-border">
                            <h3 class="box-title splitTitle">
                                <span v-if="transactions.transactions.length > 1">Split {{ index+1 }} / {{ transactions.transactions.length }}</span>
                                <span v-if="transactions.transactions.length === 1">Transaction information</span>
                            </h3>
                        </div>
                        <div class="box-body">
                            <div class="row">
                                <div class="col-lg-4">
                                    <account-select
                                            inputName="source[]"
                                            title="Source account"
                                            :accountName="transaction.source_account.name"
                                            :accountTypeFilters="transaction.source_account.allowed_types"
                                            :transactionType="transactionType"
                                            :index="index"
                                            v-on:clear:value="clearSource(index)"
                                            v-on:select:account="selectedSourceAccount(index, $event)"
                                    ></account-select>
                                    <account-select
                                            inputName="destination[]"
                                            title="Destination account"
                                            :accountName="transaction.destination_account.name"
                                            :accountTypeFilters="transaction.destination_account.allowed_types"
                                            :transactionType="transactionType"
                                            :index="index"
                                            v-on:clear:value="clearDestination(index)"
                                            v-on:select:account="selectedDestinationAccount(index, $event)"
                                    ></account-select>
                                    <div class="form-group">
                                        <div class="col-sm-12">
                                            <input type="text" class="form-control" name="description[]"
                                                   :value="transaction.description"
                                                   title="Description" autocomplete="off" placeholder="Description">
                                        </div>
                                    </div>
                                    <div class="form-group">
                                        <div class="col-sm-12">
                                            <input type="date" class="form-control" name="date[]"
                                                   title="Date" value="" autocomplete="off"
                                                   :value="transaction.date"
                                                   :disabled="index > 0"
                                                   placeholder="Date">
                                        </div>
                                    </div>
                                    <div v-if="index===0">
                                        <transaction-type
                                                :source="transaction.source_account.type"
                                                :destination="transaction.destination_account.type"
                                                v-on:set:transactionType="setTransactionType($event)"
                                                v-on:act:limitSourceType="limitSourceType($event)"
                                                v-on:act:limitDestinationType="limitDestinationType($event)"
                                        ></transaction-type>
                                    </div>
                                </div>
                                <div class="col-lg-4">
                                    <amount
                                            :source="transaction.source_account"
                                            :destination="transaction.destination_account"
                                            :transactionType="transactionType"
                                    ></amount>
                                    <foreign-amount
                                            :source="transaction.source_account"
                                            :destination="transaction.destination_account"
                                            :transactionType="transactionType"
                                    ></foreign-amount>
                                </div>
                                <div class="col-lg-4">
                                    <budget :transactionType="transactionType"></budget>
                                    <category :transactionType="transactionType"></category>
                                    <piggy-bank :transactionType="transactionType"></piggy-bank>
                                    <tags></tags>
                                    <!-- custom string fields -->
                                    <custom-transaction-fields></custom-transaction-fields>

                                    <!-- custom date fields -->

                                    <!-- custom other fields -->
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="row">
            <div class="col-lg-12">
                <p>
                    <button class="btn btn-primary" v-on:click="addTransaction">Add another split</button>
                </p>
            </div>
        </div>
    </form>
</template>

<script>
    export default {
        name: "CreateTransaction",
        components: {

        },
        mounted() {
            // not sure if something needs to happen here.
        },
        ready() {
        },
        methods: {
            addTransaction: function (e) {
                let latest = this.transactions.transactions[this.transactions.transactions.length - 1];
                this.transactions.transactions.push(latest);
                e.preventDefault();
            },
            setTransactionType: function (type) {
                this.transactionType = type;
            },
            limitSourceType: function (type) {
                let i;
                for (i = 0; i < this.transactions.transactions.length; i++) {
                    this.transactions.transactions[i].source_account.allowed_types = [type];
                }
            },
            limitDestinationType: function (type) {
                let i;
                for (i = 0; i < this.transactions.transactions.length; i++) {
                    this.transactions.transactions[i].destination_account.allowed_types = [type];
                }
            },

            selectedSourceAccount: function (index, model) {
                if (typeof model === 'string') {
                    // cant change types, only name.
                    this.transactions.transactions[index].source_account.name = model;
                } else {

                    // todo maybe replace the entire model?
                    this.transactions.transactions[index].source_account.id = model.id;
                    this.transactions.transactions[index].source_account.name = model.name;
                    this.transactions.transactions[index].source_account.type = model.type;

                    this.transactions.transactions[index].source_account.currency_id = model.currency_id;
                    this.transactions.transactions[index].source_account.currency_name = model.currency_name;
                    this.transactions.transactions[index].source_account.currency_code = model.currency_code;
                    this.transactions.transactions[index].source_account.currency_decimal_places = model.currency_decimal_places;
                    // force types on destination selector.
                    this.transactions.transactions[index].destination_account.allowed_types = window.allowedOpposingTypes.source[model.type];
                }
            },
            selectedDestinationAccount: function (index, model) {
                if (typeof model === 'string') {
                    // cant change types, only name.
                    this.transactions.transactions[index].destination_account.name = model;
                } else {

                    // todo maybe replace the entire model?
                    this.transactions.transactions[index].destination_account.id = model.id;
                    this.transactions.transactions[index].destination_account.name = model.name;
                    this.transactions.transactions[index].destination_account.type = model.type;

                    this.transactions.transactions[index].destination_account.currency_id = model.currency_id;
                    this.transactions.transactions[index].destination_account.currency_name = model.currency_name;
                    this.transactions.transactions[index].destination_account.currency_code = model.currency_code;
                    this.transactions.transactions[index].destination_account.currency_decimal_places = model.currency_decimal_places;

                    // force types on destination selector.
                    this.transactions.transactions[index].source_account.allowed_types = window.allowedOpposingTypes.destination[model.type];
                }
            },
            clearSource: function (index) {
                this.transactions.transactions[index].source_account.id = 0;
                this.transactions.transactions[index].source_account.name = "";
                this.transactions.transactions[index].source_account.type = "";
                this.transactions.transactions[index].destination_account.allowed_types = [];

                // if there is a destination model, reset the types of the source
                // by pretending we selected it again.
                if (this.transactions.transactions[index].destination_account) {
                    console.log('There is a destination account.');
                    this.selectedDestinationAccount(index, this.transactions.transactions[index].destination_account);
                }
            },
            clearDestination: function (index) {
                this.transactions.transactions[index].destination_account.id = 0;
                this.transactions.transactions[index].destination_account.name = "";
                this.transactions.transactions[index].destination_account.type = "";
                this.transactions.transactions[index].source_account.allowed_types = [];

                // if there is a source model, reset the types of the destination
                // by pretending we selected it again.
                if (this.transactions.transactions[index].source_account) {
                    console.log('There is a source account.');
                    this.selectedSourceAccount(index, this.transactions.transactions[index].source_account);
                }
            }
        },

        /*
         * The component's data.
         */
        data() {
            return {
                transactionType: null,
                transactions: {
                    group_title: "",
                    transactions: [
                        {
                            description: "",
                            date: "",
                            amount: "",
                            foreign_amount: "",
                            source_account: {
                                id: 0,
                                name: "",
                                type: "",
                                //currency_id: window.defaultCurrency.id,
                                //currency_name: window.defaultCurrency.name,
                                //currency_code: window.defaultCurrency.code,
                                //currency_decimal_places: window.defaultCurrency.decimal_places,
                                currency_id: 0,
                                currency_name: '',
                                currency_code: '',
                                currency_decimal_places: 2,
                                allowed_types: []
                            },
                            destination_account: {
                                id: 0,
                                name: "",
                                type: "",
                                //currency_id: window.defaultCurrency.id,
                                //currency_name: window.defaultCurrency.name,
                                //currency_code: window.defaultCurrency.code,
                                //currency_decimal_places: window.defaultCurrency.decimal_places,
                                currency_id: 0,
                                currency_name: '',
                                currency_code: '',
                                currency_decimal_places: 2,
                                allowed_types: []
                            }
                        }
                    ]
                }
            };
        },
    }
</script>

<style scoped>
</style>