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
    <form method="POST" action="transactions/store" accept-charset="UTF-8" class="form-horizontal" id="store"
          enctype="multipart/form-data">
        <input name="_token" type="hidden" value="xxx">

        <div class="row" v-if="transactions.length > 1">
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
                                       v-model="group_title"
                                       title="Description of the split transaction" autocomplete="off"
                                       placeholder="Description of the split transaction">


                                <p class="help-block">
                                    If you create a split transaction, there must be a global description for all splits
                                    of the transaction.
                                </p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div v-for="(transaction, index) in transactions">
            <div class="row">
                <div class="col-lg-12">
                    <div class="box">
                        <div class="box-header with-border">
                            <h3 class="box-title splitTitle">
                                <span v-if="transactions.length > 1">Split {{ index+1 }} / {{ transactions.length }}</span>
                                <span v-if="transactions.length === 1">Transaction information</span>
                            </h3>
                            <div class="box-tools pull-right" v-if="transactions.length > 1" x>
                                <button v-on:click="deleteTransaction(index, $event)" class="btn btn-xs btn-danger"><i
                                        class="fa fa-trash"></i></button>
                            </div>
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
                                                   v-model="transaction.description"
                                                   title="Description" autocomplete="off" placeholder="Description">
                                        </div>
                                    </div>
                                    <div class="form-group">
                                        <div class="col-sm-12">
                                            <input type="date" class="form-control" name="date[]"
                                                   title="Date" value="" autocomplete="off"
                                                   v-model="transaction.date"
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
                                            v-model="transaction.amount"
                                            :transactionType="transactionType"
                                    ></amount>
                                    <foreign-amount
                                            :source="transaction.source_account"
                                            :destination="transaction.destination_account"
                                            v-model="transaction.foreign_amount"
                                            :transactionType="transactionType"
                                    ></foreign-amount>
                                </div>
                                <div class="col-lg-4">
                                    <budget
                                            :transactionType="transactionType"
                                            v-model="transaction.budget"
                                    ></budget>
                                    <category
                                            :transactionType="transactionType"
                                            v-model="transaction.category"
                                    ></category>
                                    <piggy-bank
                                            :transactionType="transactionType"
                                            v-model="transaction.piggy_bank"
                                    ></piggy-bank>
                                    <tags
                                            v-model="transaction.tags"
                                    ></tags>
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
        <pre>{{ $data }}</pre>
        <div class="row">
            <div class="col-lg-12">
                <p>
                    <button class="btn btn-primary" v-on:click="addTransaction">Add another split</button>
                    <button class="btn btn-success">Submit</button>
                </p>
            </div>
        </div>
    </form>
</template>

<script>
    export default {
        name: "CreateTransaction",
        components: {},
        mounted() {
            this.addTransaction();
        },
        ready() {

        },
        methods: {
            addTransaction: function (e) {
                this.transactions.push({
                    description: "",
                    date: "",
                    amount: "",
                    category: "",
                    piggy_bank: 0,
                    budget: 0,
                    tags: [],
                    foreign_amount: {
                        amount: "",
                        currency_id: 0
                    },
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
                });
                if (e) {
                    e.preventDefault();
                }
            },
            setTransactionType: function (type) {
                this.transactionType = type;
            },
            deleteTransaction: function (index, event) {
                event.preventDefault();
                for (const key in this.transactions) {
                    if (
                        this.transactions.hasOwnProperty(key) && /^0$|^[1-9]\d*$/.test(key) && key <= 4294967294) {
                        console.log('Transactions[' + key + '] exists: ' + this.transactions[key].description);
                    }
                }

                this.transactions.splice(index, 1);
                console.log('Going to remove index ' + index);

                for (const key in this.transactions) {
                    if (
                        this.transactions.hasOwnProperty(key) && /^0$|^[1-9]\d*$/.test(key) && key <= 4294967294) {
                        console.log('New: Transactions[' + key + '] exists: ' + this.transactions[key].description);
                    }
                }
            },
            limitSourceType: function (type) {
                let i;
                for (i = 0; i < this.transactions.length; i++) {
                    this.transactions[i].source_account.allowed_types = [type];
                }
            },
            limitDestinationType: function (type) {
                let i;
                for (i = 0; i < this.transactions.length; i++) {
                    this.transactions[i].destination_account.allowed_types = [type];
                }
            },

            selectedSourceAccount: function (index, model) {
                if (typeof model === 'string') {
                    // cant change types, only name.
                    this.transactions[index].source_account.name = model;
                } else {

                    // todo maybe replace the entire model?
                    this.transactions[index].source_account.id = model.id;
                    this.transactions[index].source_account.name = model.name;
                    this.transactions[index].source_account.type = model.type;

                    this.transactions[index].source_account.currency_id = model.currency_id;
                    this.transactions[index].source_account.currency_name = model.currency_name;
                    this.transactions[index].source_account.currency_code = model.currency_code;
                    this.transactions[index].source_account.currency_decimal_places = model.currency_decimal_places;
                    // force types on destination selector.
                    this.transactions[index].destination_account.allowed_types = window.allowedOpposingTypes.source[model.type];
                }
            },
            selectedDestinationAccount: function (index, model) {
                if (typeof model === 'string') {
                    // cant change types, only name.
                    this.transactions[index].destination_account.name = model;
                } else {

                    // todo maybe replace the entire model?
                    this.transactions[index].destination_account.id = model.id;
                    this.transactions[index].destination_account.name = model.name;
                    this.transactions[index].destination_account.type = model.type;

                    this.transactions[index].destination_account.currency_id = model.currency_id;
                    this.transactions[index].destination_account.currency_name = model.currency_name;
                    this.transactions[index].destination_account.currency_code = model.currency_code;
                    this.transactions[index].destination_account.currency_decimal_places = model.currency_decimal_places;

                    // force types on destination selector.
                    this.transactions[index].source_account.allowed_types = window.allowedOpposingTypes.destination[model.type];
                }
            },
            clearSource: function (index) {
                this.transactions[index].source_account.id = 0;
                this.transactions[index].source_account.name = "";
                this.transactions[index].source_account.type = "";
                this.transactions[index].destination_account.allowed_types = [];

                // if there is a destination model, reset the types of the source
                // by pretending we selected it again.
                if (this.transactions[index].destination_account) {
                    console.log('There is a destination account.');
                    this.selectedDestinationAccount(index, this.transactions[index].destination_account);
                }
            },
            clearDestination: function (index) {
                this.transactions[index].destination_account.id = 0;
                this.transactions[index].destination_account.name = "";
                this.transactions[index].destination_account.type = "";
                this.transactions[index].source_account.allowed_types = [];

                // if there is a source model, reset the types of the destination
                // by pretending we selected it again.
                if (this.transactions[index].source_account) {
                    console.log('There is a source account.');
                    this.selectedSourceAccount(index, this.transactions[index].source_account);
                }
            }
        },

        /*
         * The component's data.
         */
        data() {
            return {
                transactionType: null,
                group_title: "",
                transactions: []
            };
        },
    }
</script>

<style scoped>
</style>