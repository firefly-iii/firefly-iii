<!--
  - EditTransaction.vue
  - Copyright (c) 2019 james@firefly-iii.org
  -
  - This file is part of Firefly III (https://github.com/firefly-iii).
  -
  - This program is free software: you can redistribute it and/or modify
  - it under the terms of the GNU Affero General Public License as
  - published by the Free Software Foundation, either version 3 of the
  - License, or (at your option) any later version.
  -
  - This program is distributed in the hope that it will be useful,
  - but WITHOUT ANY WARRANTY; without even the implied warranty of
  - MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
  - GNU Affero General Public License for more details.
  -
  - You should have received a copy of the GNU Affero General Public License
  - along with this program.  If not, see <https://www.gnu.org/licenses/>.
  -->

<template>
    <form id="store" accept-charset="UTF-8" action="#" class="form-horizontal" enctype="multipart/form-data"
          method="POST">
        <input name="_token" type="hidden" value="xxx">
        <div v-if="error_message !== ''" class="row">
            <div class="col-lg-12">
                <div class="alert alert-danger alert-dismissible" role="alert">
                    <button class="close" data-dismiss="alert" type="button"
                            v-bind:aria-label="$t('firefly.close')"><span
                        aria-hidden="true">&times;</span></button>
                    <strong>{{ $t("firefly.flash_error") }}</strong> {{ error_message }}
                </div>
            </div>
        </div>

        <div v-if="isReconciled" class="row">
            <div class="col-lg-12">
                <div class="alert alert-warning" role="alert">
                    <strong>{{ $t("firefly.flash_warning") }}</strong> {{ $t('firefly.is_reconciled_fields_dropped') }}
                </div>
            </div>
        </div>

        <div v-if="success_message !== ''" class="row">
            <div class="col-lg-12">
                <div class="alert alert-success alert-dismissible" role="alert">
                    <button class="close" data-dismiss="alert" type="button"
                            v-bind:aria-label="$t('firefly.close')"><span
                        aria-hidden="true">&times;</span></button>
                    <strong>{{ $t("firefly.flash_success") }}</strong> <span v-html="success_message"></span>
                </div>
            </div>
        </div>
        <div>
            <div v-for="(transaction, index) in transactions" class="row">
                <div class="col-lg-12">
                    <div class="box">
                        <div class="box-header with-border">
                            <h3 class="box-title splitTitle">
                <span v-if="transactions.length > 1">{{ $t('firefly.single_split') }} {{ index + 1 }} / {{
                        transactions.length
                    }}</span>
                                <span v-if="transactions.length === 1">{{
                                        $t('firefly.transaction_journal_information')
                                    }}</span>
                            </h3>
                            <div v-if="transactions.length > 1" class="box-tools pull-right">
                                <button class="btn btn-xs btn-danger" type="button"
                                        v-on:click="deleteTransaction(index, $event)"><i
                                    class="fa fa-trash"></i></button>
                            </div>
                        </div>
                        <div class="box-body">
                            <div class="row">
                                <div class="col-lg-4">
                                    <transaction-description v-if="transactionType.toLowerCase() !== 'reconciliation'"
                                                             v-model="transaction.description"
                                                             :error="transaction.errors.description"
                                                             :index="index"
                                    >
                                    </transaction-description>
                                    <account-select v-if="transactionType.toLowerCase() !== 'reconciliation'"
                                                    :accountName="transaction.source_account.name"
                                                    :accountTypeFilters="transaction.source_account.allowed_types"
                                                    :error="transaction.errors.source_account"
                                                    :index="index"
                                                    :transactionType="transactionType"
                                                    inputName="source[]"
                                                    v-bind:inputDescription="$t('firefly.source_account')"
                                                    v-on:clear:value="clearSource(index)"
                                                    v-on:select:account="selectedSourceAccount(index, $event)"
                                    ></account-select>
                                    <div v-if="transactionType.toLowerCase() === 'reconciliation'" class="form-group">
                                        <div class="col-sm-12">
                                            <p id="ffInput_source" class="form-control-static">
                                                <em>
                                                    {{ $t('firefly.source_account_reconciliation') }}
                                                </em>
                                            </p>
                                        </div>
                                    </div>
                                    <account-select v-if="transactionType.toLowerCase() !== 'reconciliation'"
                                                    :accountName="transaction.destination_account.name"
                                                    :accountTypeFilters="transaction.destination_account.allowed_types"
                                                    :error="transaction.errors.destination_account"
                                                    :index="index"
                                                    :transactionType="transactionType"
                                                    inputName="destination[]"
                                                    v-bind:inputDescription="$t('firefly.destination_account')"
                                                    v-on:clear:value="clearDestination(index)"
                                                    v-on:select:account="selectedDestinationAccount(index, $event)"
                                    ></account-select>
                                    <div v-if="transactionType.toLowerCase() === 'reconciliation'" class="form-group">
                                        <div class="col-sm-12">
                                            <p id="ffInput_dest" class="form-control-static">
                                                <em>
                                                    {{ $t('firefly.destination_account_reconciliation') }}
                                                </em>
                                            </p>
                                        </div>
                                    </div>
                                    <standard-date
                                        v-model="transaction.date"
                                        :error="transaction.errors.date"
                                        :index="index"
                                    >
                                    </standard-date>
                                    <div v-if="index===0">
                                        <transaction-type
                                            :destination="transaction.destination_account.type"
                                            :source="transaction.source_account.type"
                                            v-on:set:transactionType="setTransactionType($event)"
                                            v-on:act:limitSourceType="limitSourceType($event)"
                                            v-on:act:limitDestinationType="limitDestinationType($event)"
                                        ></transaction-type>
                                    </div>
                                </div>
                                <div class="col-lg-4">
                                    <!-- -->
                                    <amount
                                        v-model="transaction.amount"
                                        :destination="transaction.destination_account"
                                        :error="transaction.errors.amount"
                                        :index="index"
                                        :source="transaction.source_account"
                                        :transactionType="transactionType"
                                    ></amount>
                                    <foreign-amount v-if="transactionType.toLowerCase() !== 'reconciliation'"
                                                    v-model="transaction.foreign_amount"
                                                    :destination="transaction.destination_account"
                                                    :error="transaction.errors.foreign_amount"
                                                    :no_currency="$t('firefly.none_in_select_list')"
                                                    :source="transaction.source_account"
                                                    :transactionType="transactionType"
                                                    v-bind:title="$t('form.foreign_amount')"
                                    ></foreign-amount>
                                    <reconciled v-show="isReconciled"
                                                v-model="transaction.reconciled"
                                                :error="transaction.errors.reconciled"
                                    ></reconciled>
                                </div>
                                <div class="col-lg-4">
                                    <budget
                                        v-model="transaction.budget"
                                        :error="transaction.errors.budget_id"
                                        :no_budget="$t('firefly.none_in_select_list')"
                                        :transactionType="transactionType"
                                    ></budget>
                                    <category
                                        v-model="transaction.category"
                                        :error="transaction.errors.category"
                                        :transactionType="transactionType"
                                    ></category>
                                    <tags
                                        v-model="transaction.tags"
                                        :error="transaction.errors.tags"
                                        :tags="transaction.tags"
                                        :transactionType="transactionType"
                                    ></tags>
                                    <bill
                                        v-model="transaction.bill"
                                        :error="transaction.errors.bill_id"
                                        :no_bill="$t('firefly.none_in_select_list')"
                                        :transactionType="transactionType"
                                    ></bill>
                                    <custom-transaction-fields
                                        v-model="transaction.custom_fields"
                                        :error="transaction.errors.custom_errors"
                                    ></custom-transaction-fields>
                                </div>
                            </div>
                        </div>
                        <div
                            v-if="transactions.length-1 === index && transactionType.toLowerCase() !== 'reconciliation'"
                            class="box-footer">
                            <button class="btn btn-default" type="button" @click="addTransaction">{{
                                    $t('firefly.add_another_split')
                                }}
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div v-if="transactions.length > 1" class="row">
            <div class="col-lg-6 col-md-6 col-sm-12 col-xs-12">
                <div class="box">
                    <div class="box-header with-border">
                        <h3 class="box-title">
                            {{ $t('firefly.split_transaction_title') }}
                        </h3>
                    </div>
                    <div class="box-body">
                        <group-description
                            v-model="group_title"
                            :error="group_title_errors"
                        ></group-description>
                    </div>
                </div>
            </div>
        </div>
        <div class="row">
            <div class="col-lg-6 col-md-6 col-sm-12 col-xs-12">
                <div class="box">
                    <div class="box-header with-border">
                        <h3 class="box-title">
                            {{ $t('firefly.submission') }}
                        </h3>
                    </div>
                    <div class="box-body">
                        <div class="checkbox">
                            <label>
                                <input v-model="returnAfter" name="return_after" type="checkbox">
                                {{ $t('firefly.after_update_create_another') }}
                            </label>
                        </div>
                        <div v-if="null !== transactionType && transactionType.toLowerCase() !== 'reconciliation'"
                             class="checkbox">
                            <label>
                                <input v-model="storeAsNew" name="store_as_new" type="checkbox">
                                {{ $t('firefly.store_as_new') }}
                            </label>
                        </div>
                    </div>
                    <div class="box-footer">
                        <div class="btn-group">
                            <button id="submitButton" ref="submitButtonRef" class="btn btn-success" @click="submit">{{
                                    $t('firefly.update_transaction')
                                }}
                            </button>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-lg-6 col-md-6 col-sm-12 col-xs-12">
                <div class="box">
                    <div class="box-header with-border">
                        <h3 class="box-title">
                            {{ $t('firefly.submission_options') }}
                        </h3>
                    </div>
                    <div class="box-body">
                        <div class="checkbox">
                            <label>
                                <input v-model="applyRules" name="apply_rules" type="checkbox">
                                {{ $t('firefly.apply_rules_checkbox') }}
                            </label>
                        </div>
                        <div class="checkbox">
                            <label>
                                <input v-model="fireWebhooks" name="fire_webhooks" type="checkbox">
                                {{ $t('firefly.fire_webhooks_checkbox') }}

                            </label>
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
        // console.log('EditTransaction: mounted()');
        this.getGroup();
    },
    ready() {
        // console.log('EditTransaction: ready()');
    },
    methods: {
        positiveAmount(amount) {
            if (amount < 0) {
                return amount * -1;
            }
            return amount;
        },
        roundNumber(amount, decimals) {
            let multiplier = Math.pow(10, decimals);
            return Math.round(amount * multiplier) / multiplier;
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
            if(model.hasOwnProperty('account_currency_id') && null !== model.account_currency_id) {
                this.transactions[index].source_account.currency_id = model.account_currency_id;
                this.transactions[index].source_account.currency_name = model.account_currency_name;
                this.transactions[index].source_account.currency_code = model.account_currency_code;
                this.transactions[index].source_account.currency_decimal_places = model.account_currency_decimal_places;
            }
        },
        selectedDestinationAccount(index, model) {
            if (typeof model === 'string') {
                // cant change types, only name.
                // also clear ID
                this.transactions[index].destination_account.id = null;
                this.transactions[index].destination_account.name = model;
                return;
            }
            // console.log('selectedDestinationAccount');
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
            if(model.hasOwnProperty('account_currency_id') && null !== model.account_currency_id) {
                this.transactions[index].destination_account.currency_id = model.account_currency_id;
                this.transactions[index].destination_account.currency_name = model.account_currency_name;
                this.transactions[index].destination_account.currency_code = model.account_currency_code;
                this.transactions[index].destination_account.currency_decimal_places = model.account_currency_decimal_places;
            }
            // console.log('Selected destination account currency ID  = ' + this.transactions[index].destination_account.currency_id);
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
            if (null !== type) {
                this.transactionType = type;
            }
        },
        deleteTransaction(index, event) {
            event.preventDefault();
            this.transactions.splice(index, 1);
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
            // console.log('EditTransaction: getGroup()');
            const page = window.location.href.split('/');
            const groupId = parseInt(page[page.length - 1]);


            const uri = './api/v1/transactions/' + groupId;
            // console.log(uri);

            // fill in transactions array.
            axios.get(uri)
                .then(response => {
                    this.processIncomingGroup(response.data.data);
                })
                .catch(error => {
                    console.error('Some error when getting axios');
                    console.error(error);
                });
        },
        processIncomingGroup(data) {
            // console.log('EditTransaction: processIncomingGroup()');
            this.group_title = data.attributes.group_title;
            let transactions = data.attributes.transactions.reverse();
            for (let key in transactions) {
                if (transactions.hasOwnProperty(key) && /^0$|^[1-9]\d*$/.test(key) && key <= 4294967294) {
                    let transaction = transactions[key];
                    this.processIncomingGroupRow(transaction);
                }

            }
        },
        ucFirst(string) {
            if (typeof string === 'string') {
                return string.charAt(0).toUpperCase() + string.slice(1);
            }
            return null;
        },
        processIncomingGroupRow(transaction) {
            //console.log('EditTransaction: processIncomingGroupRow()');
            this.setTransactionType(transaction.type);

            if (true === transaction.reconciled) {
                this.isReconciled = true;
            }

            let newTags = [];
            for (let key in transaction.tags) {
                if (transaction.tags.hasOwnProperty(key) && /^0$|^[1-9]\d*$/.test(key) && key <= 4294967294) {
                    newTags.push({text: transaction.tags[key], tiClasses: []});
                }
            }
            // console.log('source allowed types for a ' + transaction.type);
            //console.log(window.expectedSourceTypes.source[transaction.type]);
            // console.log(window.expectedSourceTypes.source[this.ucFirst(transaction.type)]);
            // console.log('destination allowed types for a ' + transaction.type);
            // console.log(window.expectedSourceTypes.destination[this.ucFirst(transaction.type)]);
            if (typeof window.expectedSourceTypes === 'undefined') {
                console.error('window.expectedSourceTypes is unexpectedly empty.')
            }
            let result = {
                transaction_journal_id: transaction.transaction_journal_id,
                description: transaction.description,
                date: transaction.date.substring(0, 16),
                reconciled: transaction.reconciled,
                amount: this.roundNumber(this.positiveAmount(transaction.amount), transaction.currency_decimal_places),
                category: transaction.category_name,
                errors: {
                    source_account: [],
                    destination_account: [],
                    description: [],
                    amount: [],
                    date: [],
                    budget_id: [],
                    reconciled: [],
                    bill_id: [],
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
                        external_url: [],
                    },
                },
                budget: transaction.budget_id,
                bill: transaction.bill_id,
                tags: newTags,
                custom_fields: {
                    interest_date: transaction.interest_date,
                    book_date: transaction.book_date,
                    process_date: transaction.process_date,
                    due_date: transaction.due_date,
                    payment_date: transaction.payment_date,
                    invoice_date: transaction.invoice_date,
                    internal_reference: transaction.internal_reference,
                    notes: transaction.notes,
                    external_url: transaction.external_url
                },
                foreign_amount: {
                    amount: this.roundNumber(this.positiveAmount(transaction.foreign_amount), transaction.foreign_currency_decimal_places),
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
                    allowed_types: window.expectedSourceTypes.source[this.ucFirst(transaction.type)]
                },
                destination_account: {
                    id: transaction.destination_id,
                    name: transaction.destination_name,
                    type: transaction.destination_type,
                    currency_id: transaction.currency_id,
                    currency_name: transaction.currency_name,
                    currency_code: transaction.currency_code,
                    currency_decimal_places: transaction.currency_decimal_places,
                    allowed_types: window.expectedSourceTypes.destination[this.ucFirst(transaction.type)]
                }
            };
            // console.log('Source currency id is      ' + result.source_account.currency_id);
            // console.log('Destination currency id is ' + result.destination_account.currency_id);

            // if transaction type is transfer, the destination currency_id etc. MUST match the actual account currency info.
            // OR if the transaction type is a withdrawal, and the destination account is a liability account, same as above.
            if (
                ('transfer' === transaction.type && null !== transaction.foreign_currency_code) ||
                ('withdrawal' === transaction.type && ['Loan', 'Debt', 'Mortgage'].includes(transaction.destination_type) && null !== transaction.foreign_currency_code)
            ) {
                result.destination_account.currency_id = transaction.foreign_currency_id;
                result.destination_account.currency_name = transaction.foreign_currency_name;
                result.destination_account.currency_code = transaction.foreign_currency_code;
                result.destination_account.currency_decimal_places = transaction.foreign_currency_decimal_places;
                // console.log('Set destination currency_id to ' + result.destination_account.currency_id);
            }
            // if the transaction type is a deposit, but the source account is a liability, the source
            // account currency must not be overruled.

            if('deposit' === transaction.type && ['Loan', 'Debt', 'Mortgage'].includes(transaction.source_type)) {
                // console.log('Overrule for deposit from liability to ' + transaction.foreign_currency_id);
                result.destination_account.currency_id = transaction.foreign_currency_id;
                result.destination_account.currency_name = transaction.foreign_currency_name;
                result.destination_account.currency_code = transaction.foreign_currency_code;
                result.destination_account.currency_decimal_places = transaction.foreign_currency_decimal_places;
            }


            if (null === transaction.foreign_amount) {
                result.foreign_amount.amount = '';
            }
            this.transactions.push(result);
        },
        limitSourceType: function (type) {
            // let i;
            // for (i = 0; i < this.transactions.length; i++) {
            //     this.transactions[i].source_account.allowed_types = [type];
            // }
        },
        limitDestinationType: function (type) {
            // let i;
            // for (i = 0; i < this.transactions.length; i++) {
            //     this.transactions[i].destination_account.allowed_types = [type];
            // }
        },
        convertData: function () {
            // console.log('start of convertData');
            let data = {
                'apply_rules': this.applyRules,
                'fire_webhooks': this.fireWebhooks,
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

            // get currency from first transaction. overrule the rest
            let currencyId = this.transactions[0].source_account.currency_id;

            if ('deposit' === transactionType) {
                currencyId = this.transactions[0].destination_account.currency_id;
            }
            // if transaction type is deposit BUT the source account is a liability, the currency ID must be the SOURCE account ID.
            if ('deposit' === transactionType && ['Loan', 'Debt', 'Mortgage'].includes(firstSource)) {
                // console.log('Overruled currency ID to ' + this.transactions[0].source_account.currency_id);
                currencyId = this.transactions[0].source_account.currency_id;
            }
            // console.log('Final currency ID = ' + currencyId);

            for (let key in this.transactions) {
                if (this.transactions.hasOwnProperty(key) && /^0$|^[1-9]\d*$/.test(key) && key <= 4294967294) {
                    data.transactions.push(this.convertDataRow(this.transactions[key], key, transactionType, currencyId));
                }
            }
            //console.log(data);
            // console.log('end of convertData');
            return data;
        },
        convertDataRow(row, index, transactionType, currencyId) {
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

            // depends on the transaction type, where we get the currency.
            // if ('withdrawal' === transactionType || 'transfer' === transactionType) {
            //   row.currency_id = row.source_account.currency_id;
            //   console.log('Overruled currency ID to ' + row.currency_id);
            // }
            // if ('deposit' === transactionType) {
            //   row.currency_id = row.destination_account.currency_id;
            //   console.log('Overruled currency ID to ' + row.currency_id);
            // }

            row.currency_id = currencyId;
            // console.log('Final currency ID = ' + currencyId);

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
            foreignAmount = '0';
            // loop tags
            for (let tagKey in row.tags) {
                if (row.tags.hasOwnProperty(tagKey) && /^0$|^[1-9]\d*$/.test(tagKey) && tagKey <= 4294967294) {
                    tagList.push(row.tags[tagKey].text);
                }
            }
            // set foreign currency info:
            if (typeof row.foreign_amount.amount !== 'undefined' && row.foreign_amount.amount.toString() !== '' && parseFloat(row.foreign_amount.amount) !== .00) {
                foreignAmount = row.foreign_amount.amount;
                foreignCurrency = row.foreign_amount.currency_id;
            }
            if (foreignCurrency === row.currency_id) {
                // console.log('reset foreign currencyto NULL because ' + foreignCurrency + ' = ' + row.currency_id);
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

            // parse amount, if amount has exactly one comma:
            // solves issues with some locales.
            if (1 === (String(row.amount).match(/\,/g) || []).length) {
                row.amount = String(row.amount).replace(',', '.');
            }

            // console.log('Reconciled is ' + row.reconciled);

            currentArray =
                {
                    transaction_journal_id: row.transaction_journal_id,
                    type: transactionType,
                    date: date,
                    amount: row.amount,

                    description: row.description,

                    source_id: sourceId,
                    source_name: sourceName,

                    reconciled: row.reconciled,

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
                    external_url: row.custom_fields.external_url,
                    notes: row.custom_fields.notes,
                    tags: tagList
                };
            // always submit foreign amount info.
            currentArray.foreign_amount = foreignAmount;
            currentArray.foreign_currency_id = foreignCurrency;

            // only submit currency ID when not 0:
            if (0 !== row.currency_id && null !== row.currency_id) {
                currentArray.currency_id = row.currency_id;
            }

            // set budget id and piggy ID.
            currentArray.budget_id = parseInt(row.budget);
            if (parseInt(row.bill) > 0) {
                currentArray.bill_id = parseInt(row.bill);
            }
            if (0 === parseInt(row.bill)) {
                currentArray.bill_id = null;
            }

            if (parseInt(row.piggy_bank) > 0) {
                currentArray.piggy_bank_id = parseInt(row.piggy_bank);
            }
            if (this.isReconciled && !this.storeAsNew && true === row.reconciled) {
                // drop content from array:
                delete currentArray.source_id;
                delete currentArray.source_name;
                delete currentArray.destination_id;
                delete currentArray.destination_name;
                delete currentArray.amount;
                delete currentArray.foreign_amount;
                delete currentArray.foreign_currency_id;
                delete currentArray.currency_id;
                currentArray.reconciled = true;
            }
            if (true === row.isReconciled) {
                this.isReconciled = false;
            }

            return currentArray;
        },
        submit: function (e) {
            // console.log('Submit!');
            let button = $('#submitButton');
            button.prop("disabled", true);

            const page = window.location.href.split('/');
            const groupId = parseInt(page[page.length - 1]);
            let uri = './api/v1/transactions/' + groupId + '?_token=' + document.head.querySelector('meta[name="csrf-token"]').content;
            let method = 'PUT';
            if (this.storeAsNew) {
                // console.log('storeAsNew');
                // other links.
                uri = './api/v1/transactions?_token=' + document.head.querySelector('meta[name="csrf-token"]').content;
                method = 'POST';
            }
            const data = this.convertData();
            // console.log('POST!');
            axios({
                method: method,
                url: uri,
                data: data,
            }).then(response => {
                // console.log('Response!');
                if (0 === this.collectAttachmentData(response)) {
                    const title = response.data.data.attributes.group_title ?? response.data.data.attributes.transactions[0].description;
                    this.redirectUser(response.data.data.id, title);
                }
                button.removeAttr('disabled');
            }).catch(error => {
                // console.log('Error :(');
                // give user errors things back.
                // something something render errors.
                this.parseErrors(error.response.data);
                // something.
                button.removeAttr('disabled');
            });
            if (e) {
                e.preventDefault();
            }
            // console.log('DONE with method.');
        },

        redirectUser(groupId, title) {
            // console.log('Now in redirectUser');
            if (this.returnAfter) {
                this.setDefaultErrors();
                // do message if update or new:
                if (this.storeAsNew) {
                    this.success_message = this.$t('firefly.transaction_new_stored_link', {
                        ID: groupId,
                        title: this.escapeHtml(title)
                    });
                    this.error_message = '';
                } else {
                    this.success_message = this.$t('firefly.transaction_updated_link', {
                        ID: groupId,
                        title: this.escapeHtml(title)
                    });
                    this.error_message = '';
                }
            } else {
                if (this.storeAsNew) {
                    window.location.href = window.previousUrl + '?transaction_group_id=' + groupId + '&message=created';
                } else {
                    window.location.href = window.previousUrl + '?transaction_group_id=' + groupId + '&message=updated';
                }
            }
            // console.log('End of redirectUser');
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
            // console.log('Done with collectAttachmentData()');
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
                        attachable_type: 'TransactionJournal',
                        attachable_id: fileData[key].journal,
                    };
                    axios.post(uri, data)
                        .then(response => {
                            // console.log('Created attachment #' + key);
                            // console.log('Uploading attachment #' + key);
                            const uploadUri = './api/v1/attachments/' + response.data.data.id + '/upload';
                            axios.post(uploadUri, fileData[key].content)
                                .then(secondResponse => {
                                    // console.log('Uploaded attachment #' + key);
                                    uploads++;
                                    if (uploads === count) {
                                        // finally we can redirect the user onwards.
                                        // console.log('FINAL UPLOAD');
                                        this.redirectUser(groupId, null);
                                    }
                                    // console.log('Upload complete!');
                                    return true;
                                }).catch(error => {
                                console.error('Could not upload file.');
                                console.error(error);
                                uploads++;
                                this.error_message = 'Could not upload attachment: ' + error;
                                if (uploads === count) {
                                    this.redirectUser(groupId, null);
                                }
                                // console.error(error);
                                return false;
                            });
                        }).catch(error => {
                        console.error('Could not create upload.');
                        console.error(error);
                        uploads++;
                        if (uploads === count) {
                            // finally we can redirect the user onwards.
                            // console.log('FINAL UPLOAD');
                            this.redirectUser(groupId, null);
                        }
                        // console.log('Upload complete!');
                        return false;
                    });
                }
            }

        },

        escapeHtml: function (string) {

            let entityMap = {
                '&': '&amp;',
                '<': '&lt;',
                '>': '&gt;',
                '"': '&quot;',
                "'": '&#39;',
                '/': '&#x2F;',
                '`': '&#x60;',
                '=': '&#x3D;'
            };

            return String(string).replace(/[&<>"'`=\/]/g, function fromEntityMap(s) {
                return entityMap[s];
            });

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
                    bill_id: [],
                    foreign_amount: [],
                    category: [],
                    piggy_bank: [],
                    reconciled: [],
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
                        external_url: [],
                    },
                },
                budget: 0,
                bill: 0,
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
                    "attachments": [],
                    "external_url": "",
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
            let count = this.transactions.length;
            // console.log('Transactions length = ' + count);
            // also set accounts from previous entry, if present.
            if (this.transactions.length > 1) {
                // console.log('Adding split.');
                this.transactions[count - 1].source_account = this.transactions[count - 2].source_account;
                this.transactions[count - 1].destination_account = this.transactions[count - 2].destination_account;
                this.transactions[count - 1].date = this.transactions[count - 2].date;
            }
            // console.log('Transactions length now = ' + this.transactions.length);

            if (e) {
                e.preventDefault();
            }
        },
        parseErrors: function (errors) {
            this.setDefaultErrors();
            this.error_message = "";
            if (errors.message.length > 0) {
                this.error_message = this.$t('firefly.errors_submission');
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
                            case 'bill_id':
                            case 'description':
                            case 'reconciled':
                            case 'tags':
                                this.transactions[transactionIndex].errors[fieldName] = errors.errors[key];
                                break;
                            case 'external_url':
                                //console.log('Found ext error in field "' + fieldName + '": ' + errors.errors[key]);
                                this.transactions[transactionIndex].errors.custom_errors[fieldName] = errors.errors[key];
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
                        bill_id: [],
                        foreign_amount: [],
                        category: [],
                        reconciled: [],
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
                            external_url: [],
                        },
                    };
                }
            }
        },
    },


    data() {
        return {
            applyRules: true,
            fireWebhooks: true,
            group: this.groupId,
            error_message: "",
            isReconciled: false,
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
