/*
 * create.js
 * Copyright (c) 2023 james@firefly-iii.org
 *
 * This file is part of Firefly III (https://github.com/firefly-iii).
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License as
 * published by the Free Software Foundation, either version 3 of the
 * License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with this program.  If not, see <https://www.gnu.org/licenses/>.
 */

import '../../boot/bootstrap.js';
import dates from '../../pages/shared/dates.js';
import {createEmptySplit} from "./shared/create-empty-split.js";
import {parseFromEntries} from "./shared/parse-from-entries.js";
import formatMoney from "../../util/format-money.js";
import Autocomplete from "bootstrap5-autocomplete";
import Post from "../../api/v2/model/transaction/post.js";
import AttachmentPost from "../../api/v1/attachments/post.js";
import {getVariable} from "../../store/get-variable.js";
import {I18n} from "i18n-js";
import {loadTranslations} from "../../support/load-translations.js";
import Tags from "bootstrap5-tags";
import {loadCurrencies} from "./shared/load-currencies.js";
import {loadBudgets} from "./shared/load-budgets.js";
import {loadPiggyBanks} from "./shared/load-piggy-banks.js";
import {loadSubscriptions} from "./shared/load-subscriptions.js";

import L from "leaflet";

import 'leaflet/dist/leaflet.css';

// TODO upload attachments to other file
// TODO fix two maps, perhaps disconnect from entries entirely.
// TODO group title
// TODO map location from preferences
// TODO field preferences

let i18n;

const urls = {
    description: '/api/v2/autocomplete/transaction-descriptions',
    account: '/api/v2/autocomplete/accounts',
    category: '/api/v2/autocomplete/categories',
    tag: '/api/v2/autocomplete/tags',
};

let uploadAttachments = function (id, transactions) {
    console.log('Now in uploadAttachments');
    // reverse list of transactions?
    transactions = transactions.reverse();
    // array of all files to be uploaded:
    let toBeUploaded = [];
    let count = 0;
    // array with all file data.
    let fileData = [];

    // all attachments
    let attachments = document.querySelectorAll('input[name="attachments[]"]');
    console.log(attachments);
    // loop over all attachments, and add references to this array:
    for (const key in attachments) {
        if (attachments.hasOwnProperty(key) && /^0$|^[1-9]\d*$/.test(key) && key <= 4294967294) {
            console.log('Now at attachment #' + key);
            for (const fileKey in attachments[key].files) {
                if (attachments[key].files.hasOwnProperty(fileKey) && /^0$|^[1-9]\d*$/.test(fileKey) && fileKey <= 4294967294) {
                    // include journal thing.
                    console.log('Will upload #' + fileKey + ' from attachment #' + key + ' to transaction #' + transactions[key].transaction_journal_id);
                    toBeUploaded.push({
                        journal: transactions[key].transaction_journal_id, file: attachments[key].files[fileKey]
                    });
                    count++;
                }
            }
        }
    }
    console.log('Found ' + count + ' attachments.');

    // loop all uploads.
    for (const key in toBeUploaded) {
        if (toBeUploaded.hasOwnProperty(key) && /^0$|^[1-9]\d*$/.test(key) && key <= 4294967294) {
            console.log('Create file reader for file #' + key);
            // create file reader thing that will read all of these uploads
            (function (f, key) {
                let fileReader = new FileReader();
                fileReader.onloadend = function (evt) {
                    if (evt.target.readyState === FileReader.DONE) { // DONE == 2
                        console.log('Done reading file  #' + key);
                        fileData.push({
                            name: toBeUploaded[key].file.name,
                            journal: toBeUploaded[key].journal,
                            content: new Blob([evt.target.result])
                        });
                        if (fileData.length === count) {
                            console.log('Done reading file #' + key);
                            uploadFiles(fileData, id);
                        }
                    }
                };
                fileReader.readAsArrayBuffer(f.file);
            })(toBeUploaded[key], key,);
        }
    }
    return count;
}
let uploadFiles = function (fileData, id) {
    let count = fileData.length;
    let uploads = 0;
    console.log('Will now upload ' + count + ' file(s) to journal with id #' + id);

    for (const key in fileData) {
        if (fileData.hasOwnProperty(key) && /^0$|^[1-9]\d*$/.test(key) && key <= 4294967294) {
            console.log('Creating attachment #' + key);

            let poster = new AttachmentPost();
            poster.post(fileData[key].name, 'TransactionJournal', fileData[key].journal).then(response => {
                let attachmentId = parseInt(response.data.data.id);
                console.log('Created attachment #' + attachmentId + ' for key #' + key);
                console.log('Uploading attachment #' + key);
                poster.upload(attachmentId, fileData[key].content).then(attachmentResponse => {
                    // console.log('Uploaded attachment #' + key);
                    uploads++;
                    if (uploads === count) {
                        // finally we can redirect the user onwards.
                        console.log('FINAL UPLOAD, redirect user to new transaction or reset form or whatever.');
                        const event = new CustomEvent('upload-success', {some: 'details'});
                        document.dispatchEvent(event);
                        return;
                    }
                    console.log('Upload complete!');
                    // return true here.
                }).catch(error => {
                    console.error('Could not upload');
                    console.error(error);
                    // console.log('Uploaded attachment #' + key);
                    uploads++;
                    if (uploads === count) {
                        // finally we can redirect the user onwards.
                        console.log('FINAL UPLOAD, redirect user to new transaction or reset form or whatever.');
                        //this.redirectUser(groupId, transactionData);
                    }
                    // console.log('Upload complete!');
                    // return false;
                    // return false here
                });
            }).catch(error => {
                console.error('Could not create upload.');
                console.error(error);
                uploads++;
                if (uploads === count) {
                    // finally we can redirect the user onwards.
                    // console.log('FINAL UPLOAD');
                    console.log('FINAL UPLOAD, redirect user to new transaction or reset form or whatever.');
                    // this.redirectUser(groupId, transactionData);
                }
                // console.log('Upload complete!');
                //return false;
            });
        }
    }
}

let transactions = function () {
    return {
        // transactions are stored in "entries":
        entries: [],

        // state of the form is stored in formState:
        formStates: {
            loadingCurrencies: true,
            loadingBudgets: true,
            loadingPiggyBanks: true,
            loadingSubscriptions: true,
            isSubmitting: false,
            returnHereButton: false,
            saveAsNewButton: false, // edit form only
            resetButton: true,
            rulesButton: true,
            webhooksButton: true,
        },

        // form behaviour during transaction
        formBehaviour: {
            formType: 'create', foreignCurrencyEnabled: true,
        },

        // form data (except transactions) is stored in formData
        formData: {
            defaultCurrency: null,
            enabledCurrencies: [],
            nativeCurrencies: [],
            foreignCurrencies: [],
            budgets: [],
            piggyBanks: [],
            subscriptions: [],
        },

        // properties for the entire transaction group
        groupProperties: {
            transactionType: 'unknown',
            totalAmount: 0,
        },

        // notifications
        notifications: {
            error: {
                show: false,
                text: '',
                url: '',
            },
            success: {
                show: false,
                text: '',
                url: '',
            },
            wait: {
                show: false,text: '',
                url: '',

            }
        },


        // part of the account selection auto-complete
        filters: {
            source: [],
            destination: [],
        },

        // old properties, no longer used.


        // data sets

        // used to display the success message
        //newGroupTitle: '',
        //newGroupId: 0,

        // map things:
        //hasLocation: false,
        //latitude: 51.959659235274,
        //longitude: 5.756805887265858,
        //zoomLevel: 13,

        // events in the form
        changedDateTime(event) {
            console.log('changedDateTime');
        },
        changedDescription(event) {
            console.log('changedDescription');
        },
        changedDestinationAccount(event) {
            console.log('changedDestinationAccount')
        },
        changedSourceAccount(event) {
            console.log('changedSourceAccount')
        },


        detectTransactionType() {
            const sourceType = this.entries[0].source_account.type ?? 'unknown';
            const destType = this.entries[0].destination_account.type ?? 'unknown';
            if ('unknown' === sourceType && 'unknown' === destType) {
                this.groupProperties.transactionType = 'unknown';
                console.warn('Cannot infer transaction type from two unknown accounts.');
                return;
            }
            // transfer: both are the same and in strict set of account types
            if (sourceType === destType && ['Asset account', 'Loan', 'Debt', 'Mortgage'].includes(sourceType)) {
                this.groupProperties.transactionType = 'transfer';
                console.log('Transaction type is detected to be "' + this.groupProperties.transactionType + '".');

                // this also locks the amount into the amount of the source account
                // and the foreign amount (if different) in that of the destination account.
                console.log('filter down currencies for transfer.');
                this.filterNativeCurrencies(this.entries[0].source_account.currency_code);
                this.filterForeignCurrencies(this.entries[0].destination_account.currency_code);
                return;
            }
            // withdrawals:
            if ('Asset account' === sourceType && ['Expense account', 'Debt', 'Loan', 'Mortgage'].includes(destType)) {
                this.groupProperties.transactionType = 'withdrawal';
                console.log('[a] Transaction type is detected to be "' + this.groupProperties.transactionType + '".');
                this.filterNativeCurrencies(this.entries[0].source_account.currency_code);
                return;
            }
            if ('Asset account' === sourceType && 'unknown' === destType) {
                this.groupProperties.transactionType = 'withdrawal';
                console.log('[b] Transaction type is detected to be "' + this.groupProperties.transactionType + '".');
                console.log(this.entries[0].source_account);
                this.filterNativeCurrencies(this.entries[0].source_account.currency_code);
                return;
            }
            if (['Debt', 'Loan', 'Mortgage'].includes(sourceType) && 'Expense account' === destType) {
                this.groupProperties.transactionType = 'withdrawal';
                console.log('[c] Transaction type is detected to be "' + this.groupProperties.transactionType + '".');
                this.filterNativeCurrencies(this.entries[0].source_account.currency_code);
                return;
            }

            // deposits:
            if ('Revenue account' === sourceType && ['Asset account', 'Debt', 'Loan', 'Mortgage'].includes(destType)) {
                this.groupProperties.transactionType = 'deposit';
                console.log('Transaction type is detected to be "' + this.groupProperties.transactionType + '".');
                return;
            }
            if (['Debt', 'Loan', 'Mortgage'].includes(sourceType) && 'Asset account' === destType) {
                this.groupProperties.transactionType = 'deposit';
                console.log('Transaction type is detected to be "' + this.groupProperties.transactionType + '".');
                return;
            }
            console.warn('Unknown account combination between "' + sourceType + '" and "' + destType + '".');
        },

        selectSourceAccount(item, ac) {
            const index = parseInt(ac._searchInput.attributes['data-index'].value);
            document.querySelector('#form')._x_dataStack[0].$data.entries[index].source_account = {
                id: item.id,
                name: item.name,
                alpine_name: item.name,
                type: item.type,
                currency_code: item.currency_code,
            };
            console.log('Changed source account into a known ' + item.type.toLowerCase());
            document.querySelector('#form')._x_dataStack[0].detectTransactionType();
        },
        filterForeignCurrencies(code) {
            console.log('filterForeignCurrencies("' + code + '")');
            let list = [];
            let currency;
            for (let i in this.enabledCurrencies) {
                if (this.enabledCurrencies.hasOwnProperty(i)) {
                    let current = this.enabledCurrencies[i];
                    if (current.code === code) {
                        currency = current;
                    }
                }
            }
            list.push(currency);
            this.foreignCurrencies = list;
            // is he source account currency anyway:
            if (1 === list.length && list[0].code === this.entries[0].source_account.currency_code) {
                console.log('Foreign currency is same as source currency. Disable foreign amount.');
                this.foreignAmountEnabled = false;
            }
            if (1 === list.length && list[0].code !== this.entries[0].source_account.currency_code) {
                console.log('Foreign currency is NOT same as source currency. Enable foreign amount.');
                this.foreignAmountEnabled = true;
            }

            // this also forces the currency_code on ALL entries.
            for (let i in this.entries) {
                if (this.entries.hasOwnProperty(i)) {
                    this.entries[i].foreign_currency_code = code;
                }
            }
        },
        filterNativeCurrencies(code) {
            console.log('filterNativeCurrencies("' + code + '")');
            let list = [];
            let currency;
            for (let i in this.enabledCurrencies) {
                if (this.enabledCurrencies.hasOwnProperty(i)) {
                    let current = this.enabledCurrencies[i];
                    if (current.code === code) {
                        currency = current;
                    }
                }
            }
            list.push(currency);
            this.nativeCurrencies = list;
            // this also forces the currency_code on ALL entries.
            for (let i in this.entries) {
                if (this.entries.hasOwnProperty(i)) {
                    this.entries[i].currency_code = code;
                }
            }
        },
        changedAmount(e) {
            const index = parseInt(e.target.dataset.index);
            this.entries[index].amount = parseFloat(e.target.value);
            this.totalAmount = 0;
            for (let i in this.entries) {
                if (this.entries.hasOwnProperty(i)) {
                    this.totalAmount = this.totalAmount + parseFloat(this.entries[i].amount);
                }
            }
            console.log('Changed amount to ' + this.totalAmount);
        },
        selectDestAccount(item, ac) {
            const index = parseInt(ac._searchInput.attributes['data-index'].value);
            document.querySelector('#form')._x_dataStack[0].$data.entries[index].destination_account = {
                id: item.id,
                name: item.name,
                alpine_name: item.name,
                type: item.type,
                currency_code: item.currency_code,
            };
            console.log('Changed destination account into a known ' + item.type.toLowerCase());
            document.querySelector('#form')._x_dataStack[0].detectTransactionType();
        },
        changeSourceAccount(item, ac) {
            console.log('changeSourceAccount');
            if (typeof item === 'undefined') {
                const index = parseInt(ac._searchInput.attributes['data-index'].value);
                let source = document.querySelector('#form')._x_dataStack[0].$data.entries[index].source_account;
                if (source.name === ac._searchInput.value) {
                    console.warn('Ignore hallucinated source account name change to "' + ac._searchInput.value + '"');
                    document.querySelector('#form')._x_dataStack[0].detectTransactionType();
                    return;
                }
                document.querySelector('#form')._x_dataStack[0].$data.entries[index].source_account = {
                    name: ac._searchInput.value, alpine_name: ac._searchInput.value,
                };

                console.log('Changed source account into a unknown account called "' + ac._searchInput.value + '"');
                document.querySelector('#form')._x_dataStack[0].detectTransactionType();
            }
        },
        changeDestAccount(item, ac) {
            let destination = document.querySelector('#form')._x_dataStack[0].$data.entries[0].destination_account;
            if (typeof item === 'undefined') {
                const index = parseInt(ac._searchInput.attributes['data-index'].value);
                let destination = document.querySelector('#form')._x_dataStack[0].$data.entries[index].destination_account;

                if (destination.name === ac._searchInput.value) {
                    console.warn('Ignore hallucinated destination account name change to "' + ac._searchInput.value + '"');
                    document.querySelector('#form')._x_dataStack[0].detectTransactionType();
                    return;
                }
                document.querySelector('#form')._x_dataStack[0].$data.entries[index].destination_account = {
                    name: ac._searchInput.value, alpine_name: ac._searchInput.value,
                };
                console.log('Changed destination account into a unknown account called "' + ac._searchInput.value + '"');
                document.querySelector('#form')._x_dataStack[0].detectTransactionType();
            }
        },
        changeCategory(item, ac) {
            const index = parseInt(ac._searchInput.attributes['data-index'].value);
            if (typeof item !== 'undefined' && item.name) {
                //this.entries[0].category_name = object.name;
                document.querySelector('#form')._x_dataStack[0].$data.entries[index].category_name = item.name;
                return;
            }
            document.querySelector('#form')._x_dataStack[0].$data.entries[index].category_name = ac._searchInput.value;
        },

        changeDescription(item, ac) {
            const index = parseInt(ac._searchInput.attributes['data-index'].value);
            if (typeof item !== 'undefined' && item.description) {
                //this.entries[0].category_name = object.name;
                document.querySelector('#form')._x_dataStack[0].$data.entries[index].description = item.description;
                return;
            }
            document.querySelector('#form')._x_dataStack[0].$data.entries[index].description = ac._searchInput.value;
        },

        addedSplit() {
            console.log('addedSplit');
            // TODO improve code location
            Autocomplete.init("input.ac-source", {
                server: urls.account,
                serverParams: {
                    types: this.filters.source,
                },
                fetchOptions: {
                    headers: {
                        'X-CSRF-TOKEN': document.head.querySelector('meta[name="csrf-token"]').content
                    }
                },
                hiddenInput: true,
                preventBrowserAutocomplete: true,
                highlightTyped: true,
                liveServer: true,
                onChange: this.changeSourceAccount,
                onSelectItem: this.selectSourceAccount,
                onRenderItem: function (item, b, c) {
                    return item.name_with_balance + '<br><small class="text-muted">' + i18n.t('firefly.account_type_' + item.type) + '</small>';
                }
            });

            Autocomplete.init("input.ac-category", {
                server: urls.category,
                fetchOptions: {
                    headers: {
                        'X-CSRF-TOKEN': document.head.querySelector('meta[name="csrf-token"]').content
                    }
                },
                valueField: "id",
                labelField: "name",
                highlightTyped: true,
                onSelectItem: this.changeCategory,
                onChange: this.changeCategory,
            });

            Autocomplete.init("input.ac-dest", {
                server: urls.account,
                serverParams: {
                    types: this.filters.destination,
                },
                fetchOptions: {
                    headers: {
                        'X-CSRF-TOKEN': document.head.querySelector('meta[name="csrf-token"]').content
                    }
                },
                hiddenInput: true,
                preventBrowserAutocomplete: true,
                liveServer: true,
                highlightTyped: true,
                onSelectItem: this.selectDestAccount,
                onChange: this.changeDestAccount,
                onRenderItem: function (item, b, c) {
                    return item.name_with_balance + '<br><small class="text-muted">' + i18n.t('firefly.account_type_' + item.type) + '</small>';
                }
            });
            this.filters.destination = [];
            Autocomplete.init('input.ac-description', {
                server: urls.description,
                fetchOptions: {
                    headers: {
                        'X-CSRF-TOKEN': document.head.querySelector('meta[name="csrf-token"]').content
                    }
                },
                valueField: "id",
                labelField: "description",
                highlightTyped: true,
                onSelectItem: this.changeDescription,
                onChange: this.changeDescription,
            });


        },
        processUpload(event) {
            console.log('I am ALSO event listener for upload-success!');
            console.log(event);
            this.showBarOrRedirect();
        },

        init() {
            Promise.all([getVariable('language', 'en_US')]).then((values) => {
                i18n = new I18n();
                const locale = values[0].replace('-', '_');
                i18n.locale = locale;
                loadTranslations(i18n, locale).then(() => {
                    this.addSplit();
                });
            });
            // load currencies and save in form data.
            loadCurrencies().then(data => {
                this.formStates.loadingCurrencies = false;
                this.formData.defaultCurrency = data.defaultCurrency;
                this.formData.enabledCurrencies = data.enabledCurrencies;
                this.formData.nativeCurrencies = data.nativeCurrencies;
                this.formData.foreignCurrencies = data.foreignCurrencies;
            });

            loadBudgets().then(data => {
                this.formData.budgets = data;
                this.formStates.loadingBudgets = false;
            });
            loadPiggyBanks().then(data => {
                this.formData.piggyBanks = data;
                this.formStates.loadingPiggyBanks = false;
            });
            loadSubscriptions().then(data => {
                this.formData.subscriptions = data;
                this.formStates.loadingSubscriptions = false;
            });

            document.addEventListener('upload-success', (event) => {
                this.processUpload(event);
            });


            // source can never be expense account
            this.filters.source = ['Asset account', 'Loan', 'Debt', 'Mortgage', 'Revenue account'];
            // destination can never be revenue account
            this.filters.destination = ['Expense account', 'Loan', 'Debt', 'Mortgage', 'Asset account'];
        },
        submitTransaction() {
            // reset all views:
            this.submitting = true;
            this.showSuccessMessage = false;
            this.showErrorMessage = false;
            this.showWaitmessage = false;
            this.detectTransactionType();

            // parse transaction:
            let transactions = parseFromEntries(this.entries, this.groupProperties.transactionType);
            let submission = {
                // todo process all options
                group_title: null, fire_webhooks: false, apply_rules: false, transactions: transactions
            };
            if (transactions.length > 1) {
                // todo improve me
                submission.group_title = transactions[0].description;
            }

            // submit the transaction. Multi-stage process thing going on here!
            let poster = new Post();
            console.log(submission);
            poster.post(submission).then((response) => {
                // submission was a success.
                this.newGroupId = parseInt(response.data.data.id);
                this.newGroupTitle = submission.group_title ?? submission.transactions[0].description
                const attachmentCount = uploadAttachments(this.newGroupId, response.data.data.attributes.transactions);

                // upload transactions? then just show the wait message and do nothing else.
                if (attachmentCount > 0) {
                    this.showWaitMessage = true;
                    return;
                }

                // if not, respond to user options:
                this.showBarOrRedirect();
            }).catch((error) => {
                this.submitting = false;
                console.log(error);
                // todo put errors in form
                if (typeof error.response !== 'undefined') {
                    this.parseErrors(error.response.data);
                }


            });
        },
        showBarOrRedirect() {
            this.showWaitMessage = false;
            this.submitting = false;
            if (this.returnHereButton) {
                // todo create success banner
                this.showSuccessMessage = true;
                this.successMessageLink = 'transactions/show/' + this.newGroupId;
                this.successMessageText = i18n.t('firefly.stored_journal_js', {description: this.newGroupTitle});
                // todo clear out form if necessary
                if (this.resetButton) {
                    this.entries = [];
                    this.addSplit();
                    this.totalAmount = 0;
                }
            }

            if (!this.returnHereButton) {
                window.location = 'transactions/show/' + this.newGroupId + '?transaction_group_id=' + this.newGroupId + '&message=created';
            }
        },
        parseErrors(data) {
            this.setDefaultErrors();
            this.showErrorMessage = true;
            this.showSuccessMessage = false;
            // todo create error banner.
            this.errorMessageText = i18n.t('firefly.errors_submission') + ' ' + data.message;
            let transactionIndex;
            let fieldName;

            // todo add 'was-validated' to form.
            console.log('Now processing errors.');
            for (const key in data.errors) {
                if (data.errors.hasOwnProperty(key)) {
                    if (key === 'group_title') {
                        console.log('Handling group title error.');
                        // todo handle group errors.
                        //this.group_title_errors = errors.errors[key];
                    }
                    if (key !== 'group_title') {
                        console.log('Handling errors for ' + key);
                        // lol, the dumbest way to explode "transactions.0.something" ever.
                        transactionIndex = parseInt(key.split('.')[1]);
                        fieldName = key.split('.')[2];
                        console.log('Transaction index: ' + transactionIndex);
                        console.log('Field name: ' + fieldName);
                        console.log('Errors');
                        console.log(data.errors[key]);
                        // set error in this object thing.
                        switch (fieldName) {
                            case 'currency_code':
                            case 'foreign_currency_code':
                            case 'category_name':
                            case 'piggy_bank_id':
                            case 'notes':
                            case 'internal_reference':
                            case 'external_url':
                            case 'latitude':
                            case 'longitude':
                            case 'zoom_level':
                            case 'interest_date':
                            case 'book_date':
                            case 'process_date':
                            case 'due_date':
                            case 'payment_date':
                            case 'invoice_date':
                            case 'amount':
                            case 'date':
                            case 'budget_id':
                            case 'bill_id':
                            case 'description':
                            case 'tags':
                                this.entries[transactionIndex].errors[fieldName] = data.errors[key];
                                break;
                            case 'source_name':
                            case 'source_id':
                                this.entries[transactionIndex].errors.source_account = this.entries[transactionIndex].errors.source_account.concat(data.errors[key]);
                                break;
                            case 'type':
                                // put the error in the description:
                                this.entries[transactionIndex].errors.description = this.entries[transactionIndex].errors.source_account.concat(data.errors[key]);
                                break;
                            case 'destination_name':
                            case 'destination_id':
                                this.entries[transactionIndex].errors.destination_account = this.entries[transactionIndex].errors.destination_account.concat(data.errors[key]);
                                break;
                            case 'foreign_amount':
                            case 'foreign_currency_id':
                                this.entries[transactionIndex].errors.foreign_amount = this.entries[transactionIndex].errors.foreign_amount.concat(data.errors[key]);
                                break;
                        }
                    }
                    // unique some things
                    if (typeof this.entries[transactionIndex] !== 'undefined') {
                        this.entries[transactionIndex].errors.source_account = Array.from(new Set(this.entries[transactionIndex].errors.source_account));
                        this.entries[transactionIndex].errors.destination_account = Array.from(new Set(this.entries[transactionIndex].errors.destination_account));
                    }
                }
            }
            console.log(this.entries[0].errors);
        },
        setDefaultErrors() {

        },
        addSplit() {
            this.entries.push(createEmptySplit());
            setTimeout(() => {
                // render tags:
                Tags.init('select.ac-tags', {
                    allowClear: true,
                    server: urls.tag,
                    liveServer: true,
                    clearEnd: true,
                    allowNew: true,
                    notFoundMessage: '(nothing found)',
                    noCache: true,
                    fetchOptions: {
                        headers: {
                            'X-CSRF-TOKEN': document.head.querySelector('meta[name="csrf-token"]').content
                        }
                    }
                });
                const count = this.entries.length - 1;
                let map = L.map('location_map_' + count).setView([this.latitude, this.longitude], this.zoomLevel);

                L.tileLayer('https://tile.openstreetmap.org/{z}/{x}/{y}.png', {
                    maxZoom: 19,
                    attribution: '&copy; <a href="http://www.openstreetmap.org/copyright">OpenStreetMap ' + count + '</a>'
                }).addTo(map);
                map.on('click', this.addPointToMap);
                map.on('zoomend', this.saveZoomOfMap);
                this.entries[count].map

                // const id = 'location_map_' + count;
                // const map = () => {
                //     const el = document.getElementById(id),
                //         map = L.map(id).setView([this.latitude, this.longitude], this.zoomLevel)
                //     L.tileLayer(
                //         'https://tile.openstreetmap.org/{z}/{x}/{y}.png',
                //         {attribution: '&copy; <a href="http://www.openstreetmap.org/copyright">OpenStreetMap '+count+'</a>'}
                //     ).addTo(map)
                //     map.on('click', this.addPointToMap);
                //     map.on('zoomend', this.saveZoomOfMap);
                //     return map
                // }
                // this.entries[count].map = map();

            }, 250);

        },
        removeSplit(index) {
            this.entries.splice(index, 1);
            // fall back to index 0
            const triggerFirstTabEl = document.querySelector('#split-0-tab')
            triggerFirstTabEl.click();
        },
        formattedTotalAmount() {
            return formatMoney(this.totalAmount, 'EUR');
        },
        clearLocation(e) {
            e.preventDefault();
            const target = e.currentTarget;
            const index = parseInt(target.attributes['data-index'].value);
            this.entries[index].hasLocation = false;
            this.entries[index].marker.remove();
            return false;
        },
        saveZoomOfMap(e) {
            let index = parseInt(e.sourceTarget._container.attributes['data-index'].value);
            let map = document.querySelector('#form')._x_dataStack[0].$data.entries[index].map;
            document.querySelector('#form')._x_dataStack[0].$data.entries[index].zoomLevel = map.getZoom();
            console.log('New zoom level: ' + map.getZoom());
        },
        addPointToMap(e) {
            let index = parseInt(e.originalEvent.currentTarget.attributes['data-index'].value);
            let map = document.querySelector('#form')._x_dataStack[0].$data.entries[index].map;
            let hasLocation = document.querySelector('#form')._x_dataStack[0].$data.entries[index].hasLocation;
            console.log('Has location: ' + hasLocation);
            if (false === hasLocation) {
                console.log('False!');
                const marker = new L.marker(e.latlng, {draggable: true});
                marker.on('dragend', function (event) {
                    var marker = event.target;

                    var position = marker.getLatLng();
                    marker.setLatLng(new L.LatLng(position.lat, position.lng), {draggable: 'true'});
                    document.querySelector('#form')._x_dataStack[0].$data.entries[index].latitude = position.lat;
                    document.querySelector('#form')._x_dataStack[0].$data.entries[index].longitude = position.lng;
                });

                marker.addTo(map);
                document.querySelector('#form')._x_dataStack[0].$data.entries[index].hasLocation = true;
                document.querySelector('#form')._x_dataStack[0].$data.entries[index].marker = marker;
                document.querySelector('#form')._x_dataStack[0].$data.entries[index].latitude = e.latlng.lat;
                document.querySelector('#form')._x_dataStack[0].$data.entries[index].longitude = e.latlng.lng;
                document.querySelector('#form')._x_dataStack[0].$data.entries[index].zoomLevel = map.getZoom();
            }
        }
    }
}

let comps = {transactions, dates};

function loadPage() {
    Object.keys(comps).forEach(comp => {
        console.log(`Loading page component "${comp}"`);
        let data = comps[comp]();
        Alpine.data(comp, () => data);
    });
    Alpine.start();
}

document.addEventListener('upload-success', (event) => {
    console.log('I am event listener for upload-success');
    console.log(event);
    //Alpine.
});


// <button x-data @click="$dispatch('custom-event', 'Hello World!')">

// wait for load until bootstrapped event is received.
document.addEventListener('firefly-iii-bootstrapped', () => {
    console.log('Loaded through event listener.');
    loadPage();
});
// or is bootstrapped before event is triggered.
if (window.bootstrapped) {
    console.log('Loaded through window variable.');
    loadPage();
}
