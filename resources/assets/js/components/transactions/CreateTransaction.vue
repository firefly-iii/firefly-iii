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
        <div class="row" v-if="invalid_submission !== ''">
            <div class="col-lg-12">
                <div class="alert alert-danger alert-dismissible" role="alert">
                    <button type="button" class="close" data-dismiss="alert" aria-label="Close"><span
                            aria-hidden="true">&times;</span></button>
                    <strong>Error!</strong> {{ invalid_submission }}
                </div>
            </div>
        </div>

        <div class="row" v-if="success_message !== ''">
            <div class="col-lg-12">
                <div class="alert alert-success alert-dismissible" role="alert">
                    <button type="button" class="close" data-dismiss="alert" aria-label="Close"><span
                            aria-hidden="true">&times;</span></button>
                    <strong>Success!</strong> {{ success_message }}
                </div>
            </div>
        </div>
        <div class="row" v-if="transactions.length > 1">
            <div class="col-lg-6">
                <div class="box">
                    <div class="box-header with-border">
                        <h3 class="box-title">
                            Description of the split transaction
                        </h3>
                    </div>
                    <div class="box-body">
                        <group-description
                                :error="group_title_errors"
                                v-model="group_title"
                        ></group-description>
                    </div>
                </div>
            </div>
        </div>

        <div>
            <div class="row" v-for="(transaction, index) in transactions">
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
                                            :error="transaction.errors.source_account"
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
                                            :error="transaction.errors.destination_account"
                                    ></account-select>
                                    <transaction-description
                                            v-model="transaction.description"
                                            :index="index"
                                            :error="transaction.errors.description"
                                    >
                                    </transaction-description>
                                    <standard-date
                                            v-model="transaction.date"
                                            :index="index"
                                            :error="transaction.errors.date"
                                    >
                                    </standard-date>
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
                                            :error="transaction.errors.amount"
                                            :transactionType="transactionType"
                                    ></amount>
                                    <foreign-amount
                                            :source="transaction.source_account"
                                            :destination="transaction.destination_account"
                                            v-model="transaction.foreign_amount"
                                            :transactionType="transactionType"
                                            :error="transaction.errors.foreign_amount"
                                    ></foreign-amount>
                                </div>
                                <div class="col-lg-4">
                                    <budget
                                            :transactionType="transactionType"
                                            v-model="transaction.budget"
                                            :error="transaction.errors.budget_id"
                                    ></budget>
                                    <category
                                            :transactionType="transactionType"
                                            v-model="transaction.category"
                                            :error="transaction.errors.category"
                                    ></category>
                                    <piggy-bank
                                            :transactionType="transactionType"
                                            v-model="transaction.piggy_bank"
                                            :error="transaction.errors.piggy_bank"
                                    ></piggy-bank>
                                    <tags
                                            v-model="transaction.tags"
                                            :error="transaction.errors.tags"
                                    ></tags>
                                    <custom-transaction-fields
                                            v-model="transaction.custom_fields"
                                            :error="transaction.errors.custom_errors"
                                    ></custom-transaction-fields>
                                </div>
                            </div>
                        </div>
                        <div class="box-footer" v-if="transactions.length-1 === index">
                            <button class="btn btn-primary" @click="addTransaction">Add another split</button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="row">
            <div class="col-lg-3 col-md-4 col-sm-6 col-xs-12">
                <div class="box">
                    <div class="box-header with-border">
                        <h3 class="box-title">
                            Submission
                        </h3>
                    </div>
                    <div class="box-body">
                        <div class="checkbox">
                            <label>
                                <input v-model="createAnother" name="create_another" type="checkbox">
                                After storing, return here to create another one.
                            </label>
                            <label v-bind:class="{ 'text-muted': this.createAnother === false}">
                                <input v-model="resetFormAfter" :disabled="this.createAnother === false"
                                       name="reset_form" type="checkbox">
                                Reset form after submission

                            </label>
                        </div>
                    </div>
                    <div class="box-footer">
                        <div class="btn-group">
                            <button class="btn btn-success" @click="submit">Submit</button>
                            <button class="btn btn-primary" @click="addTransaction">Add another split</button>
                        </div>
                    </div>
                </div>
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
            convertData: function () {
                let data = {
                    'transactions': [],
                };
                let tagList = [];
                let transactionType;
                let firstSource;
                let firstDestination;
                let foreignAmount = null;
                let foreignCurrency = null;
                let currentArray;
                let sourceId;
                let sourceName;
                let destId;
                let destName;
                let date;

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

                        sourceId = this.transactions[key].source_account.id;
                        sourceName = this.transactions[key].source_account.name;
                        destId = this.transactions[key].destination_account.id;
                        destName = this.transactions[key].destination_account.name;

                        date = this.transactions[key].date;
                        if (key > 0) {
                            date = this.transactions[0].date;
                        }

                        // if type is 'withdrawal' and destination is empty, cash withdrawal.
                        if (transactionType === 'withdrawal' && '' === destName) {
                            destId = window.cashAccountId;
                        }

                        // if type is 'deposit' and source is empty, cash deposit.
                        if (transactionType === 'deposit' && '' === sourceName) {
                            sourceId = window.cashAccountId;
                        }

                        // if key is over 0 and type is withdrawal or transfer, take source from key 0.
                        if (key > 0 && (transactionType.toLowerCase() === 'withdrawal' || transactionType.toLowerCase() === 'transfer')) {
                            sourceId = this.transactions[0].source_account.id;
                            sourceName = this.transactions[0].source_account.name;
                        }

                        // if key is over 0 and type is deposit or transfer, take destination from key 0.
                        if (key > 0 && (transactionType.toLowerCase() === 'deposit' || transactionType.toLowerCase() === 'transfer')) {
                            destId = this.transactions[0].destination_account.id;
                            destName = this.transactions[0].destination_account.name;
                        }

                        tagList = [];
                        foreignAmount = null;
                        foreignCurrency = null;
                        // loop tags
                        for (let tagKey in this.transactions[key].tags) {
                            if (this.transactions[key].tags.hasOwnProperty(tagKey) && /^0$|^[1-9]\d*$/.test(tagKey) && key <= 4294967294) {
                                tagList.push(this.transactions[key].tags[tagKey].text);
                            }
                        }

                        // set foreign currency info:
                        if (this.transactions[key].foreign_amount.amount !== '' && parseFloat(this.transactions[key].foreign_amount.amount) !== .00) {
                            foreignAmount = this.transactions[key].foreign_amount.amount;
                            foreignCurrency = this.transactions[key].foreign_amount.currency_id;
                        }
                        if (foreignCurrency === this.transactions[key].currency_id) {
                            foreignAmount = null;
                            foreignCurrency = null;
                        }

                        currentArray =
                            {
                                type: transactionType,
                                date: date,

                                amount: this.transactions[key].amount,
                                currency_id: this.transactions[key].currency_id,

                                description: this.transactions[key].description,

                                source_id: sourceId,
                                source_name: sourceName,

                                destination_id: destId,
                                destination_name: destName,


                                category_name: this.transactions[key].category,
                                //budget_id: this.transactions[key].budget,
                                //piggy_bank_id: this.transactions[key].piggy_bank,


                                interest_date: this.transactions[key].custom_fields.interest_date,
                                book_date: this.transactions[key].custom_fields.book_date,
                                process_date: this.transactions[key].custom_fields.process_date,
                                due_date: this.transactions[key].custom_fields.due_date,
                                payment_date: this.transactions[key].custom_fields.payment_date,
                                invoice_date: this.transactions[key].custom_fields.invoice_date,
                                internal_reference: this.transactions[key].custom_fields.internal_reference,
                                notes: this.transactions[key].custom_fields.notes
                            };

                        if (tagList.length > 0) {
                            currentArray.tags = tagList;
                        }
                        if (null !== foreignAmount) {
                            currentArray.foreign_amount = foreignAmount;
                            currentArray.foreign_currency_id = foreignCurrency;
                        }
                        // set budget id and piggy ID.
                        if (parseInt(this.transactions[key].budget) > 0) {
                            currentArray.budget_id = parseInt(this.transactions[key].budget);
                        }
                        if (parseInt(this.transactions[key].piggy_bank) > 0) {
                            currentArray.piggy_bank_id = parseInt(this.transactions[key].piggy_bank);
                        }

                        data.transactions.push(currentArray);
                    }
                }
                //console.log(data);

                return data;
            },
            submit(e) {
                const uri = './api/v1/transactions?_token=' + document.head.querySelector('meta[name="csrf-token"]').content;
                const data = this.convertData();
                if (this.resetFormAfter) {
                    this.resetTransactions();
                }
                axios.post(uri, data)
                    .then(response => {
                        if (this.createAnother) {
                            // do message:
                            this.success_message = 'The transaction has been stored.';
                            this.invalid_submission = '';
                            if (this.resetFormAfter) {
                                this.addTransaction();
                            }
                        } else {
                            window.location.href = 'transactions/show/' + response.data.data.id + '?message=created';
                        }
                    }).catch(error => {
                    // give user errors things back.
                    // something something render errors.
                    this.parseErrors(error.response.data);
                    // something.
                });
                if (e) {
                    e.preventDefault();
                }
            },
            setDefaultErrors: function () {
                for (const key in this.transactions) {
                    if (this.transactions.hasOwnProperty(key) && /^0$|^[1-9]\d*$/.test(key) && key <= 4294967294) {
                        this.transactions[key].errors = {
                            source_account: [],
                            destination_account: [],
                            description: [],
                            amount: [],
                            date: [],
                            budget_id: [],
                            foreign_amount: [],
                            category: [],
                            piggy_bank: [],
                            tags: [],
                            // custom fields:
                            custom_errors: {
                                interest_date: [],
                                book_date: [],
                                process_date: [],
                                due_date: [],
                                payment_date: [],
                                invoice_date: [],
                                internal_reference: [],
                                notes: [],
                                attachments: [],
                            },
                        };
                    }
                }
            },
            parseErrors: function (errors) {
                this.setDefaultErrors();
                this.invalid_submission = "";
                if (errors.message.length > 0) {
                    this.invalid_submission = "There was something wrong with your submission. Please check out the errors below.";
                } else {
                    this.invalid_submission = '';
                }
                let transactionIndex;
                let fieldName;

                for (const key in errors.errors) {
                    if (errors.errors.hasOwnProperty(key)) {
                        if (key === 'group_title') {
                            this.group_title_errors = errors.errors[key];
                        }
                        if (key !== 'group_title') {
                            // lol dumbest way to explode "transactions.0.something" ever.
                            transactionIndex = parseInt(key.split('.')[1]);
                            fieldName = key.split('.')[2];
                            // set error in this object thing.
                            switch (fieldName) {
                                case 'amount':
                                case 'date':
                                case 'budget_id':
                                case 'description':
                                case 'tags':
                                    this.transactions[transactionIndex].errors[fieldName] = errors.errors[key];
                                    break;
                                case 'source_name':
                                case 'source_id':
                                    this.transactions[transactionIndex].errors.source_account =
                                        this.transactions[transactionIndex].errors.source_account.concat(errors.errors[key]);
                                    break;
                                case 'destination_name':
                                case 'destination_id':
                                    this.transactions[transactionIndex].errors.destination_account =
                                        this.transactions[transactionIndex].errors.destination_account.concat(errors.errors[key]);
                                    break;
                                case 'foreign_amount':
                                case 'foreign_currency_id':
                                    this.transactions[transactionIndex].errors.foreign_amount =
                                        this.transactions[transactionIndex].errors.foreign_amount.concat(errors.errors[key]);
                                    break;
                            }
                        }
                    }
                }
            },
            resetTransactions: function () {
                this.transactions = [];
            },
            addTransaction: function (e) {
                this.transactions.push({
                    description: "",
                    date: "",
                    amount: "",
                    category: "",
                    piggy_bank: 0,
                    errors: {
                        source_account: [],
                        destination_account: [],
                        description: [],
                        amount: [],
                        date: [],
                        budget_id: [],
                        foreign_amount: [],
                        category: [],
                        piggy_bank: [],
                        tags: [],
                        // custom fields:
                        custom_errors: {
                            interest_date: [],
                            book_date: [],
                            process_date: [],
                            due_date: [],
                            payment_date: [],
                            invoice_date: [],
                            internal_reference: [],
                            notes: [],
                            attachments: [],
                        },
                    },
                    budget: 0,
                    tags: [],
                    custom_fields: {
                        "interest_date": "",
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
                console.log(this.transactions);
            },
            setTransactionType: function (type) {
                this.transactionType = type;
            },
            deleteTransaction: function (index, event) {
                event.preventDefault();
                for (const key in this.transactions) {
                    if (
                        this.transactions.hasOwnProperty(key) && /^0$|^[1-9]\d*$/.test(key) && key <= 4294967294) {
                    }
                }

                this.transactions.splice(index, 1);

                for (const key in this.transactions) {
                    if (
                        this.transactions.hasOwnProperty(key) && /^0$|^[1-9]\d*$/.test(key) && key <= 4294967294) {
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
                    this.transactions[index].source_account = {
                        id: model.id,
                        name: model.name,
                        type: model.type,
                        currency_id: model.currency_id,
                        currency_name: model.currency_name,
                        currency_code: model.currency_code,
                        currency_decimal_places: model.currency_decimal_places,
                        allowed_types: this.transactions[index].source_account.allowed_types
                    };

                    // force types on destination selector.
                    this.transactions[index].destination_account.allowed_types = window.allowedOpposingTypes.source[model.type];
                }
            },
            selectedDestinationAccount: function (index, model) {
                if (typeof model === 'string') {
                    // cant change types, only name.
                    this.transactions[index].destination_account.name = model;
                } else {
                    this.transactions[index].destination_account = {
                        id: model.id,
                        name: model.name,
                        type: model.type,
                        currency_id: model.currency_id,
                        currency_name: model.currency_name,
                        currency_code: model.currency_code,
                        currency_decimal_places: model.currency_decimal_places,
                        allowed_types: this.transactions[index].destination_account.allowed_types
                    };

                    // force types on destination selector.
                    this.transactions[index].source_account.allowed_types = window.allowedOpposingTypes.destination[model.type];
                }
            },
            clearSource: function (index) {
                console.log('clearSource(' + index + ')');
                // reset source account:
                this.transactions[index].source_account = {
                    id: 0,
                    name: '',
                    type: '',
                    currency_id: 0,
                    currency_name: '',
                    currency_code: '',
                    currency_decimal_places: 2,
                    allowed_types: this.transactions[index].source_account.allowed_types
                };
                // reset destination allowed account types.
                this.transactions[index].destination_account.allowed_types = [];

                // if there is a destination model, reset the types of the source
                // by pretending we selected it again.
                if (this.transactions[index].destination_account) {
                    this.selectedDestinationAccount(index, this.transactions[index].destination_account);
                }
            },
            clearDestination: function (index) {
                console.log('clearDestination(' + index + ')');
                // reset destination account:
                this.transactions[index].destination_account = {
                    id: 0,
                    name: '',
                    type: '',
                    currency_id: 0,
                    currency_name: '',
                    currency_code: '',
                    currency_decimal_places: 2,
                    allowed_types: this.transactions[index].destination_account.allowed_types
                };
                // reset destination allowed account types.
                this.transactions[index].source_account.allowed_types = [];

                // if there is a source model, reset the types of the destination
                // by pretending we selected it again.
                if (this.transactions[index].source_account) {
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
                transactions: [],
                group_title_errors: [],
                invalid_submission: "",
                success_message: "",
                cash_account_id: 0,
                createAnother: false,
                resetFormAfter: false,
                resetButtonDisabled: true,
            };
        },
    }
</script>

<style scoped>
</style>