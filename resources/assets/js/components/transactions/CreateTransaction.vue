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
                                    <custom-transaction-fields
                                            v-model="transaction.custom_fields"></custom-transaction-fields>
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
                    <button class="btn btn-primary" @click="addTransaction">Add another split</button>
                    <button class="btn btn-success" @click="submit">Submit</button>
                </p>
            </div>
        </div>
        <pre>{{ $data }}</pre>
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
            convertData: function () {
                let data = {
                    'transactions': [],
                };
                let tagList = [];
                let transactionType;
                let firstSource;
                let firstDestination;
                if (this.transactions.length > 1) {
                    data.group_title = this.group_title;
                }

                // get transaction type from first transaction
                transactionType = this.transactionType ? this.transactionType.toLowerCase() : 'invalid';

                // if the transaction type is invalid, might just be that we can deduce it from
                // the presence of a source or destination account
                firstSource = this.transactions[0].source_account.type;
                firstDestination = this.transactions[0].destination_account.type;


                if ('invalid' === transactionType && ['Asset account', 'Loan', 'Debt', 'Mortgage'].includes(firstSource)) {
                    //console.log('Assumed this is a withdrawal.');
                    transactionType = 'withdrawal';
                }

                if ('invalid' === transactionType && ['Asset account', 'Loan', 'Debt', 'Mortgage'].includes(firstDestination)) {
                    //console.log('Assumed this is a deposit.');
                    transactionType = 'deposit';
                }

                for (let key in this.transactions) {
                    if (this.transactions.hasOwnProperty(key) && /^0$|^[1-9]\d*$/.test(key) && key <= 4294967294) {
                        tagList = [];

                        // loop tags
                        for (let tagKey in this.transactions[key].tags) {
                            if (this.transactions[key].tags.hasOwnProperty(tagKey) && /^0$|^[1-9]\d*$/.test(tagKey) && key <= 4294967294) {
                                tagList.push(this.transactions[key].tags[tagKey].text);
                            }
                        }


                        data.transactions.push(
                            {
                                type: transactionType,
                                date: this.transactions[key].date,

                                amount: this.transactions[key].amount,
                                currency_id: this.transactions[key].currency_id,

                                foreign_amount: this.transactions[key].foreign_amount.amount,
                                foreign_currency_id: this.transactions[key].foreign_amount.currency_id,

                                description: this.transactions[key].description,

                                source_id: this.transactions[key].source_account.id,
                                source_name: this.transactions[key].source_account.name,

                                destination_id: this.transactions[key].destination_account.id,
                                destination_name: this.transactions[key].destination_account.name,

                                budget_id: this.transactions[key].budget,
                                category_name: this.transactions[key].category,
                                piggy_bank_id: this.transactions[key].piggy_bank,
                                tags: tagList,

                                interest_date: this.transactions[key].custom_fields.interest_date,
                                book_date: this.transactions[key].custom_fields.book_date,
                                process_date: this.transactions[key].custom_fields.process_date,
                                due_date: this.transactions[key].custom_fields.due_date,
                                payment_date: this.transactions[key].custom_fields.payment_date,
                                invoice_date: this.transactions[key].custom_fields.invoice_date,
                                internal_reference: this.transactions[key].custom_fields.internal_reference,
                                notes: this.transactions[key].custom_fields.notes
                            }
                        );
                    }
                }
                //console.log(data);

                return data;
            },
            submit(e) {
                const uri = './api/v1/transactions?_token=' + document.head.querySelector('meta[name="csrf-token"]').content;
                const data = this.convertData();

                axios.post(uri, data)
                    .then(response => {
                        console.log('OK!');
                        //console.log(response);
                    }).catch(error => {
                        // give user errors things back.
                        console.log('error!');

                        // something something render errors.

                        console.log(error.response.data);
                        // something.
                });
                if (e) {
                    e.preventDefault();
                }
            },
            addTransaction: function (e) {
                this.transactions.push({
                    description: "",
                    date: "",
                    amount: "",
                    category: "",
                    piggy_bank: 0,
                    budget: 0,
                    tags: [],
                    custom_fields: {
                        "interest_date": "2010-01-01",
                        "book_date": "",
                        "process_date": "",
                        "due_date": "",
                        "payment_date": "",
                        "invoice_date": "",
                        "internal_reference": "",
                        "notes": "",
                        "attachments": []
                    },
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