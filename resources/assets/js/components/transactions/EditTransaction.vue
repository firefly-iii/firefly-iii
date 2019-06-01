<!--
  - EditTransaction.vue
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
    <form method="POST" action="transactions/update" accept-charset="UTF-8" class="form-horizontal" id="store"
          enctype="multipart/form-data">
        <input name="_token" type="hidden" value="xxx">
        <div class="row" v-if="error_message !== ''">
            <div class="col-lg-12">
                <div class="alert alert-danger alert-dismissible" role="alert">
                    <button type="button" class="close" data-dismiss="alert" aria-label="Close"><span
                            aria-hidden="true">&times;</span></button>
                    <strong>Error!</strong> {{ error_message }}
                </div>
            </div>
        </div>

        <div class="row" v-if="success_message !== ''">
            <div class="col-lg-12">
                <div class="alert alert-success alert-dismissible" role="alert">
                    <button type="button" class="close" data-dismiss="alert" aria-label="Close"><span
                            aria-hidden="true">&times;</span></button>
                    <strong>Success!</strong> <span v-html="success_message"></span>
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
                                    <!--
                                    <piggy-bank
                                            :transactionType="transactionType"
                                            v-model="transaction.piggy_bank"
                                            :error="transaction.errors.piggy_bank"
                                    ></piggy-bank>
                                    -->
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
                                <input v-model="returnAfter" name="return_after" type="checkbox">
                                After updating, return here to create another one.
                            </label>
                        </div>
                    </div>
                    <div class="box-footer">
                        <div class="btn-group">
                            <button class="btn btn-success" @click="submit">Update</button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </form>
</template>

<script>
    export default {
        name: "EditTransaction",
        props: {
            groupId: Number
        },
        mounted() {
            this.getGroup();
        },
        ready() {
            console.log('Ready Group ID: ' + this.groupId);
        },
        methods: {
            positiveAmount: function (amount) {
                if (amount < 0) {
                    return amount * -1;
                }
                return amount;
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
                    //this.transactions[index].destination_account.allowed_types = window.allowedOpposingTypes.source[model.type];
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
                    //this.transactions[index].source_account.allowed_types = window.allowedOpposingTypes.destination[model.type];
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
                // this.transactions[index].destination_account.allowed_types = [];

                // if there is a destination model, reset the types of the source
                // by pretending we selected it again.
                if (this.transactions[index].destination_account) {
                    this.selectedDestinationAccount(index, this.transactions[index].destination_account);
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
                    }
                }

                this.transactions.splice(index, 1);

                for (const key in this.transactions) {
                    if (
                        this.transactions.hasOwnProperty(key) && /^0$|^[1-9]\d*$/.test(key) && key <= 4294967294) {
                    }
                }
            },
            clearDestination: function (index) {
                console.log('clearDestination(' + index + ')');
                // reset destination account:
                console.log('Destination allowed types first:');
                console.log(this.transactions[index].destination_account.allowed_types);
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
                //this.transactions[index].source_account.allowed_types = [];

                // if there is a source model, reset the types of the destination
                // by pretending we selected it again.
                if (this.transactions[index].source_account) {
                    this.selectedSourceAccount(index, this.transactions[index].source_account);
                }

                console.log('Destination allowed types after:');
                console.log(this.transactions[index].destination_account.allowed_types);
            },
            getGroup: function () {

                const page = window.location.href.split('/');
                const groupId = page[page.length - 1];


                const uri = './api/v1/transactions/' + groupId + '?_token=' + document.head.querySelector('meta[name="csrf-token"]').content;
                console.log(uri);

                // fill in transactions array.
                axios.get(uri)
                    .then(response => {
                        console.log(response.data.data);
                        this.group_title = response.data.data.attributes.group_title;
                        let transactions = response.data.data.attributes.transactions.reverse();
                        for (let key in transactions) {
                            if (transactions.hasOwnProperty(key) && /^0$|^[1-9]\d*$/.test(key) && key <= 4294967294) {
                                let transaction = transactions[key];
                                console.log(transactions[key]);
                                this.transactions.push({
                                    description: transaction.description,
                                    date: transaction.date.substr(0, 10),
                                    amount: this.positiveAmount(transaction.amount),
                                    category: transaction.category_name,
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
                                    budget: transaction.budget_id,
                                    tags: transaction.tags,
                                    custom_fields: {
                                        interest_date: transaction.interest_date,
                                        book_date: transaction.book_date,
                                        process_date: transaction.process_date,
                                        due_date: transaction.due_date,
                                        payment_date: transaction.payment_date,
                                        invoice_date: transaction.invoice_date,
                                        internal_reference: transaction.internal_reference,
                                        notes: transaction.notes
                                    },
                                    foreign_amount: {
                                        amount: this.positiveAmount(transaction.foreign_amount),
                                        currency_id: transaction.foreign_currency_id
                                    },
                                    source_account: {
                                        id: transaction.source_id,
                                        name: transaction.source_name,
                                        type: transaction.source_type,
                                        // i dont know these
                                        currency_id: transaction.currency_id,
                                        currency_name: transaction.currency_name,
                                        currency_code: transaction.currency_code,
                                        currency_decimal_places: transaction.currency_decimal_places,
                                        allowed_types: [transaction.source_type]
                                    },
                                    destination_account: {
                                        id: transaction.destination_id,
                                        name: transaction.destination_name,
                                        type: transaction.destination_type,
                                        currency_id: transaction.currency_id,
                                        currency_name: transaction.currency_name,
                                        currency_code: transaction.currency_code,
                                        currency_decimal_places: transaction.currency_decimal_places,
                                        allowed_types: [transaction.destination_type]
                                    }
                                });
                            }

                        }
                    })
                    .catch(error => {
                        console.error('Some error.');
                    });
            },
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

                        // correct some id's
                        if (0 === destId) {
                            destId = null;
                        }
                        if (0 === sourceId) {
                            sourceId = null;
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
            submit: function (e) {
                console.log('I am submit');
                const page = window.location.href.split('/');
                const groupId = page[page.length - 1];
                const uri = './api/v1/transactions/' + groupId + '?_token=' + document.head.querySelector('meta[name="csrf-token"]').content;
                const data = this.convertData();

                let button = $(e.currentTarget);
                button.prop("disabled", true);

                axios.put(uri, data)
                    .then(response => {
                        if (this.returnAfter) {
                            // do message:
                            this.success_message = '<a href="transactions/show/' + response.data.data.id + '">The transaction</a> has been updated.';
                            this.error_message = '';
                            button.prop("disabled", false);
                            // TODO better
                            if (this.resetFormAfter) {
                                this.getGroup();
                            }

                        } else {
                            window.location.href = 'transactions/show/' + response.data.data.id + '?message=updated';
                        }
                    }).catch(error => {
                    // give user errors things back.
                    // something something render errors.
                    this.parseErrors(error.response.data);
                    // something.
                    button.prop("disabled", false);
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
            }
        },


        data() {
            return {
                group: this.groupId,
                error_message: "",
                success_message: "",
                transactions: [],
                group_title: "",
                returnAfter: false,
                transactionType: null,
                group_title_errors: [],
                resetButtonDisabled: true,
            }
        }
    }
</script>

<style scoped>

</style>