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
    <form method="POST" action="#" accept-charset="UTF-8" class="form-horizontal" id="store"
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
                                <button  type="button" v-on:click="deleteTransaction(index, $event)" class="btn btn-xs btn-danger"><i
                                        class="fa fa-trash"></i></button>
                            </div>
                        </div>
                        <div class="box-body">
                            <div class="row">
                                <div class="col-lg-4">
                                    <transaction-description
                                            v-model="transaction.description"
                                            :index="index"
                                            :error="transaction.errors.description"
                                    >
                                    </transaction-description>
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
                                    <tags
                                            :tags="transaction.tags"
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
                            <button class="btn btn-primary" type="button" @click="addTransaction">Add another split</button>
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
                                After updating, return here to continue editing.
                            </label>
                        </div>
                        <div class="checkbox">
                            <label>
                                <input v-model="storeAsNew" name="store_as_new" type="checkbox">
                                Store as a new transaction instead of updating.
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
            // console.log('Ready Group ID: ' + this.groupId);
        },
        methods: {
            positiveAmount(amount) {
                if (amount < 0) {
                    return amount * -1;
                }
                return amount;
            },
            selectedSourceAccount(index, model) {
                if (typeof model === 'string') {
                    // cant change types, only name.
                    // also clear ID
                    this.transactions[index].source_account.id = null;
                    this.transactions[index].source_account.name = model;
                    return;
                }
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
            },
            selectedDestinationAccount(index, model) {
                if (typeof model === 'string') {
                    // cant change types, only name.
                    // also clear ID
                    this.transactions[index].destination_account.id = null;
                    this.transactions[index].destination_account.name = model;
                    return;
                }
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
            },
            clearSource(index) {
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
                // if there is a destination model, reset the types of the source
                // by pretending we selected it again.
                if (this.transactions[index].destination_account) {
                    this.selectedDestinationAccount(index, this.transactions[index].destination_account);
                }
            },
            setTransactionType(type) {
                this.transactionType = type;
            },
            deleteTransaction(index, event) {
                event.preventDefault();
                for (const key in this.transactions) {
                    if (
                        this.transactions.hasOwnProperty(key) && /^0$|^[1-9]\d*$/.test(key) && key <= 4294967294) {
                        // TODO empty iff?
                    }
                }

                this.transactions.splice(index, 1);

                for (const key in this.transactions) {
                    if (
                        this.transactions.hasOwnProperty(key) && /^0$|^[1-9]\d*$/.test(key) && key <= 4294967294) {
                        // TODO empty iff?
                    }
                }
            },
            clearDestination(index) {
                // console.log('clearDestination(' + index + ')');
                // reset destination account:
                // console.log('Destination allowed types first:');
                // console.log(this.transactions[index].destination_account.allowed_types);
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

                // console.log('Destination allowed types after:');
                // console.log(this.transactions[index].destination_account.allowed_types);
            },
            getGroup() {

                const page = window.location.href.split('/');
                const groupId = page[page.length - 1];


                const uri = './api/v1/transactions/' + groupId + '?_token=' + document.head.querySelector('meta[name="csrf-token"]').content;
                // console.log(uri);

                // fill in transactions array.
                axios.get(uri)
                    .then(response => {
                        this.processIncomingGroup(response.data.data);
                    })
                    .catch(error => {
                        //console.error('Some error.');
                    });
            },
            processIncomingGroup(data) {
                // console.log(data);
                this.group_title = data.attributes.group_title;
                let transactions = data.attributes.transactions.reverse();
                for (let key in transactions) {
                    if (transactions.hasOwnProperty(key) && /^0$|^[1-9]\d*$/.test(key) && key <= 4294967294) {
                        let transaction = transactions[key];
                        this.processIncomingGroupRow(transaction);
                    }

                }
            },
            processIncomingGroupRow(transaction) {
                // console.log(transaction);
                this.setTransactionType(transaction.type);

                let newTags = [];
                for(let key in transaction.tags) {
                    if (transaction.tags.hasOwnProperty(key) && /^0$|^[1-9]\d*$/.test(key) && key <= 4294967294) {
                        newTags.push({text: transaction.tags[key], tiClasses: []});
                    }
                }

                this.transactions.push({
                    transaction_journal_id: transaction.transaction_journal_id,
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
                    tags: newTags,
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
            },
            convertData: function () {
                let data = {
                    'transactions': [],
                };
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
                        data.transactions.push(this.convertDataRow(this.transactions[key], key, transactionType));
                    }
                }
                //console.log(data);

                return data;
            },
            convertDataRow(row, index, transactionType) {
                let tagList = [];
                let foreignAmount = null;
                let foreignCurrency = null;
                let currentArray;
                let sourceId;
                let sourceName;
                let destId;
                let destName;
                let date;
                sourceId = row.source_account.id;
                sourceName = row.source_account.name;
                destId = row.destination_account.id;
                destName = row.destination_account.name;

                date = row.date;
                if (index > 0) {
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

                // if index is over 0 and type is withdrawal or transfer, take source from index 0.
                if (index > 0 && (transactionType.toLowerCase() === 'withdrawal' || transactionType.toLowerCase() === 'transfer')) {
                    sourceId = this.transactions[0].source_account.id;
                    sourceName = this.transactions[0].source_account.name;
                }

                // if index is over 0 and type is deposit or transfer, take destination from index 0.
                if (index > 0 && (transactionType.toLowerCase() === 'deposit' || transactionType.toLowerCase() === 'transfer')) {
                    destId = this.transactions[0].destination_account.id;
                    destName = this.transactions[0].destination_account.name;
                }

                tagList = [];
                foreignAmount = null;
                foreignCurrency = null;
                // loop tags
                for (let tagKey in row.tags) {
                    if (row.tags.hasOwnProperty(tagKey) && /^0$|^[1-9]\d*$/.test(tagKey) && tagKey <= 4294967294) {
                        tagList.push(row.tags[tagKey].text);
                    }
                }
                // set foreign currency info:
                if (row.foreign_amount.amount !== '' && parseFloat(row.foreign_amount.amount) !== .00) {
                    foreignAmount = row.foreign_amount.amount;
                    foreignCurrency = row.foreign_amount.currency_id;
                }
                if (foreignCurrency === row.currency_id) {
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
                        transaction_journal_id: row.transaction_journal_id,
                        type: transactionType,
                        date: date,
                        amount: row.amount,
                        currency_id: row.currency_id,

                        description: row.description,

                        source_id: sourceId,
                        source_name: sourceName,

                        destination_id: destId,
                        destination_name: destName,


                        category_name: row.category,

                        interest_date: row.custom_fields.interest_date,
                        book_date: row.custom_fields.book_date,
                        process_date: row.custom_fields.process_date,
                        due_date: row.custom_fields.due_date,
                        payment_date: row.custom_fields.payment_date,
                        invoice_date: row.custom_fields.invoice_date,
                        internal_reference: row.custom_fields.internal_reference,
                        notes: row.custom_fields.notes,
                        tags: tagList
                    };
                if (null !== foreignAmount) {
                    currentArray.foreign_amount = foreignAmount;
                    currentArray.foreign_currency_id = foreignCurrency;
                }
                // set budget id and piggy ID.
                currentArray.budget_id = parseInt(row.budget);

                if (parseInt(row.piggy_bank) > 0) {
                    currentArray.piggy_bank_id = parseInt(row.piggy_bank);
                }

                return currentArray;
            },
            submit: function (e) {
                const page = window.location.href.split('/');
                const groupId = page[page.length - 1];
                let uri = './api/v1/transactions/' + groupId + '?_token=' + document.head.querySelector('meta[name="csrf-token"]').content;
                let method = 'PUT';
                if (this.storeAsNew) {
                    // other links.
                    uri = './api/v1/transactions?_token=' + document.head.querySelector('meta[name="csrf-token"]').content;
                    method = 'POST';
                }
                const data = this.convertData();

                let button = $(e.currentTarget);
                button.prop("disabled", true);

                //axios.put(uri, data)
                axios({
                          method: method,
                          url: uri,
                          data: data,
                      }).then(response => {

                        if (0 === this.collectAttachmentData(response)) {
                            this.redirectUser(response.data.data.id, button);
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

            redirectUser(groupId, button) {
                // console.log('In redirectUser()');
                // if count is 0, send user onwards.

                if (this.returnAfter) {
                    this.setDefaultErrors();
                    // do message if update or new:
                    if (this.storeAsNew) {
                        this.success_message = '<a href="transactions/show/' + groupId + '">Transaction #' + groupId + '</a> has been created.';
                        this.error_message = '';
                    } else {
                        this.success_message = '<a href="transactions/show/' + groupId + '">The transaction</a> has been updated.';
                        this.error_message = '';
                    }
                    button.prop("disabled", false);
                } else {
                    if (this.storeAsNew) {
                        window.location.href = window.previousUri + '?transaction_group_id=' + groupId + '&message=created';
                    } else {
                        window.location.href = window.previousUri + '?transaction_group_id=' + groupId + '&message=updated';
                    }
                }
            },

            collectAttachmentData(response) {
                // console.log('Now incollectAttachmentData()');
                let groupId = response.data.data.id;

                // array of all files to be uploaded:
                let toBeUploaded = [];

                // array with all file data.
                let fileData = [];

                // all attachments
                let attachments = $('input[name="attachments[]"]');

                // loop over all attachments, and add references to this array:
                for (const key in attachments) {
                    if (attachments.hasOwnProperty(key) && /^0$|^[1-9]\d*$/.test(key) && key <= 4294967294) {
                        for (const fileKey in attachments[key].files) {
                            if (attachments[key].files.hasOwnProperty(fileKey) && /^0$|^[1-9]\d*$/.test(fileKey) && fileKey <= 4294967294) {
                                // include journal thing.

                                let transactions = response.data.data.attributes.transactions.reverse();

                                toBeUploaded.push(
                                    {
                                        journal: transactions[key].transaction_journal_id,
                                        file: attachments[key].files[fileKey]
                                    }
                                );
                            }
                        }
                    }
                }
                let count = toBeUploaded.length;
                // console.log('Found ' + toBeUploaded.length + ' attachments.');

                // loop all uploads.
                for (const key in toBeUploaded) {
                    if (toBeUploaded.hasOwnProperty(key) && /^0$|^[1-9]\d*$/.test(key) && key <= 4294967294) {
                        // create file reader thing that will read all of these uploads
                        (function (f, i, theParent) {
                            let fileReader = new FileReader();
                            fileReader.onloadend = function (evt) {
                                if (evt.target.readyState === FileReader.DONE) { // DONE == 2
                                    fileData.push(
                                        {
                                            name: toBeUploaded[key].file.name,
                                            journal: toBeUploaded[key].journal,
                                            content: new Blob([evt.target.result])
                                        }
                                    );
                                    if (fileData.length === count) {
                                        theParent.uploadFiles(fileData, groupId);
                                    }
                                }
                            };
                            fileReader.readAsArrayBuffer(f.file);
                        })(toBeUploaded[key], key, this);
                    }
                }
                return count;
            },

            uploadFiles(fileData, groupId) {
                let count = fileData.length;
                let uploads = 0;
                for (const key in fileData) {
                    if (fileData.hasOwnProperty(key) && /^0$|^[1-9]\d*$/.test(key) && key <= 4294967294) {
                        // console.log('Creating attachment #' + key);
                        // axios thing, + then.
                        const uri = './api/v1/attachments';
                        const data = {
                            filename: fileData[key].name,
                            model: 'TransactionJournal',
                            model_id: fileData[key].journal,
                        };
                        axios.post(uri, data)
                            .then(response => {
                                // console.log('Created attachment #' + key);
                                // console.log('Uploading attachment #' + key);
                                const uploadUri = './api/v1/attachments/' + response.data.data.id + '/upload';
                                axios.post(uploadUri, fileData[key].content)
                                    .then(response => {
                                        // console.log('Uploaded attachment #' + key);
                                        uploads++;
                                        if (uploads === count) {
                                            // finally we can redirect the user onwards.
                                            // console.log('FINAL UPLOAD');
                                            this.redirectUser(groupId);
                                        }
                                        // console.log('Upload complete!');
                                        return true;
                                    }).catch(error => {
                                    // console.error('Could not upload');
                                    // console.error(error);
                                    return false;
                                });
                            });
                    }
                }

            },


            addTransaction: function (e) {
                this.transactions.push({
                    transaction_journal_id: 0,
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
            },
            parseErrors: function (errors) {
                this.setDefaultErrors();
                this.error_message = "";
                if (errors.message.length > 0) {
                    this.error_message = "There was something wrong with your submission. Please check out the errors below.";
                } else {
                    this.error_message = '';
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

                            // unique some things
                            this.transactions[transactionIndex].errors.source_account =
                                Array.from(new Set(this.transactions[transactionIndex].errors.source_account));
                            this.transactions[transactionIndex].errors.destination_account =
                                Array.from(new Set(this.transactions[transactionIndex].errors.destination_account));
                        }
                    }
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
        },


        data() {
            return {
                group: this.groupId,
                error_message: "",
                success_message: "",
                transactions: [],
                group_title: "",
                returnAfter: false,
                storeAsNew: false,
                transactionType: null,
                group_title_errors: [],
                resetButtonDisabled: true,
            }
        }
    }
</script>

<style scoped>

</style>