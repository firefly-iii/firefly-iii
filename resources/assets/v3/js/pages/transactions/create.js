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
import sidebar from '../../pages/shared/sidebar.js';
import dates from '../shared/dates.js';
import {createEmptySplit, getAccount, defaultErrorSet} from "./shared/create-empty-split.js";
import {parseFromEntries} from "./shared/parse-from-entries.js";
import formatMoney from "../../util/format-money.js";
import Post from "../../api/model/transaction/post.js";
import Delete from "../../api/model/transaction/delete.js";
import {loadCurrencies} from "./shared/load-currencies.js";
import {loadBudgets} from "./shared/load-budgets.js";
import {loadPiggyBanks} from "./shared/load-piggy-banks.js";
import {loadSubscriptions} from "./shared/load-subscriptions.js";
//
// import 'leaflet/dist/leaflet.css';
import {addAllAutocompleteToForm, getUrls} from "./shared/add-autocomplete.js";
import {processAttachments} from "./shared/process-attachments.js";
import {spliceErrorsIntoTransactions} from "./shared/splice-errors-into-transactions.js";
// import {addLocation} from "./shared/manage-locations.js";
import i18next from "i18next";
// TODO fix tags
// TODO upload attachments to other file
// TODO fix two maps, perhaps disconnect from entries entirely.
// TODO group title
// TODO map location from preferences
// TODO field preferences

const urls = getUrls();

let create = function () {
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
            resetButton: false,
            rulesButton: true,
            webhooksButton: true,
            categorySelectVisible: false
        },

        // form behavior during transaction
        formBehaviour: {
            formType: 'create',
            foreignCurrencyEnabled: true,
        },

        // form data (except transactions) is stored in formData
        formData: {
            primaryCurrency: null,
            defaultCurrency: null,
            enabledCurrencies: [],
            primaryCurrencies: [],
            foreignCurrencies: [],
            budgets: [],
            piggyBanks: [],
            subscriptions: [],
        },

        // properties for the entire transaction group
        groupProperties: {
            transactionType: 'unknown',
            title: null,
            id: null,
            totalAmount: 0,
        },

        // notifications
        notifications: {
            error: {
                show: false, text: '', url: '',
            }, success: {
                show: false, text: '', url: '',
            }, wait: {
                show: false, text: '',

            }
        },


        // part of the account selection auto-complete
        filters: {
            source: [], destination: [],
        },

        // events in the form
        changedDateTime(event) {
            console.warn('changedDateTime, event is not used');
        },

        changedDescription(event) {
            console.warn('changedDescription, event is not used');
        },

        changedDestinationAccount(event) {
            this.detectTransactionType();
        },

        changedSourceAccount(event) {
            this.detectTransactionType();
        },
        disableSplitAccounts() {
            if(this.entries.length > 1) {
                // disable source and/or destination, based on account type.
                for(let i = 1;i<this.entries.length;i++) {
                    // disable source when withdrawal or transfer
                    if('transfer' === this.groupProperties.transactionType || 'withdrawal' === this.groupProperties.transactionType) {
                        this.entries[i].source_account.disabled = true;
                        console.log('Disable source account #' + i);
                    }
                    // disable destination when deposit or transfer
                    if('transfer' === this.groupProperties.transactionType || 'deposit' === this.groupProperties.transactionType) {
                        this.entries[i].destination_account.disabled = true;
                        console.log('Disable destination account #' + i);
                    }
                }
            }
        },

        detectTransactionType() {
            const sourceType = this.entries[0].source_account.type ?? 'unknown';
            const destType = this.entries[0].destination_account.type ?? 'unknown';
            if ('unknown' === sourceType && 'unknown' === destType) {
                this.groupProperties.transactionType = 'unknown';
                console.warn('Cannot infer transaction type from two unknown accounts.');
                this.disableSplitAccounts();
                return;
            }

            // transfer: both are the same and in strict set of account types
            if (sourceType === destType && ['Asset account', 'Loan', 'Debt', 'Mortgage'].includes(sourceType)) {
                this.groupProperties.transactionType = 'transfer';
                console.log('Transaction type is detected to be "' + this.groupProperties.transactionType + '".');

                // this also locks the amount into the amount of the source account
                // and the foreign amount (if different) in that of the destination account.
                console.log('filter down currencies for transfer.');
                this.filterPrimaryCurrencies(this.entries[0].source_account.currency_code);
                this.filterForeignCurrencies(this.entries[0].destination_account.currency_code);
                this.disableSplitAccounts();
                return;
            }
            // withdrawals:
            if ('Asset account' === sourceType && ['Expense account', 'Debt', 'Loan', 'Mortgage'].includes(destType)) {
                this.groupProperties.transactionType = 'withdrawal';
                console.log('[a] Transaction type is detected to be "' + this.groupProperties.transactionType + '".');
                this.filterPrimaryCurrencies(this.entries[0].source_account.currency_code);
                this.disableSplitAccounts();
                return;
            }
            if ('Asset account' === sourceType && 'unknown' === destType) {
                this.groupProperties.transactionType = 'withdrawal';
                console.log('[b] Transaction type is detected to be "' + this.groupProperties.transactionType + '".');
                console.log(this.entries[0].source_account);
                this.filterPrimaryCurrencies(this.entries[0].source_account.currency_code);
                this.disableSplitAccounts();
                return;
            }
            if (['Debt', 'Loan', 'Mortgage'].includes(sourceType) && 'Expense account' === destType) {
                this.groupProperties.transactionType = 'withdrawal';
                console.log('[c] Transaction type is detected to be "' + this.groupProperties.transactionType + '".');
                this.filterPrimaryCurrencies(this.entries[0].source_account.currency_code);
                this.disableSplitAccounts();
                return;
            }

            // deposits:
            if ('Revenue account' === sourceType && ['Asset account', 'Debt', 'Loan', 'Mortgage'].includes(destType)) {
                this.groupProperties.transactionType = 'deposit';
                console.log('Transaction type is detected to be "' + this.groupProperties.transactionType + '".');
                this.disableSplitAccounts();
                return;
            }
            if ('unknown' === sourceType && ['Asset account', 'Debt', 'Loan', 'Mortgage'].includes(destType)) {
                this.groupProperties.transactionType = 'deposit';
                console.log('Transaction type is detected to be "' + this.groupProperties.transactionType + '".');
                this.disableSplitAccounts();
                return;
            }
            if ('Expense account' === sourceType && ['Asset account', 'Debt', 'Loan', 'Mortgage'].includes(destType)) {
                this.groupProperties.transactionType = 'deposit';
                console.warn('FORCE transaction type to be "' + this.groupProperties.transactionType + '".');
                this.entries[0].source_account.id = '';
                this.disableSplitAccounts();
                return;
            }
            if (['Debt', 'Loan', 'Mortgage'].includes(sourceType) && 'Asset account' === destType) {
                this.groupProperties.transactionType = 'deposit';
                console.log('Transaction type is detected to be "' + this.groupProperties.transactionType + '".');
                this.disableSplitAccounts();
                return;
            }
            console.warn('Unknown account combination between "' + sourceType + '" and "' + destType + '".');
            this.disableSplitAccounts();
        },

        formattedTotalAmount() {
            if (this.entries.length === 0) {
                return formatMoney(this.groupProperties.totalAmount, 'EUR');
            }
            return formatMoney(this.groupProperties.totalAmount, this.entries[0].currency_code ?? 'EUR');
        },

        filterForeignCurrencies(code) {
            let list = [];
            let currency;
            for (let i in this.formData.enabledCurrencies) {
                if (this.formData.enabledCurrencies.hasOwnProperty(i)) {
                    let current = this.formData.enabledCurrencies[i];
                    if (current.code === code) {
                        currency = current;
                    }
                }
            }
            list.push(currency);
            this.formData.foreignCurrencies = list;
            // is he source account currency anyway:
            if (1 === list.length && list[0].code === this.entries[0].source_account.currency_code) {
                console.log('Foreign currency is same as source currency. Disable foreign amount.');
                this.formBehaviour.foreignCurrencyEnabled = false;
            }
            if (1 === list.length && list[0].code !== this.entries[0].source_account.currency_code) {
                console.log('Foreign currency is NOT same as source currency. Enable foreign amount.');
                this.formBehaviour.foreignCurrencyEnabled = true;
            }

            // this also forces the currency_code on ALL entries.
            for (let i in this.entries) {
                if (this.entries.hasOwnProperty(i)) {
                    this.entries[i].foreign_currency_code = code;
                }
            }
        },

        filterPrimaryCurrencies(code) {
            let list = [];
            let currency;
            for (let i in this.formData.enabledCurrencies) {
                if (this.formData.enabledCurrencies.hasOwnProperty(i)) {
                    let current = this.formData.enabledCurrencies[i];
                    if (current.code === code) {
                        currency = current;
                    }
                }
            }
            list.push(currency);
            this.formData.primaryCurrencies = list;

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
            this.groupProperties.totalAmount = 0;
            for (let i in this.entries) {
                if (this.entries.hasOwnProperty(i)) {
                    this.groupProperties.totalAmount = this.groupProperties.totalAmount + parseFloat(this.entries[i].amount);
                }
            }
        },

        addedSplit() {
            addAllAutocompleteToForm(this.filters);
        },

        processUpload(event) {
            console.log('Now in processUpload()');
            this.showMessageOrRedirectUser();
        },

        processUploadError(event) {
            console.log('Now in processUploadError()');
            this.notifications.success.show = false;
            this.notifications.wait.show = false;
            this.notifications.error.show = true;
            this.formStates.isSubmitting = false;
            this.notifications.error.text = i18next.t('firefly.errors_upload');
            console.log(event.detail.error.response.status);
            if(413 === event.detail.error.response.status) {
                this.notifications.error.text = i18next.t('firefly.upload_too_large');
            }
            // delete transaction and let user try again.
            let del = new Delete();
            del.delete(this.groupProperties.id);
            // console.error(event);
        },
        clearDescription(index) {
            this.entries[index].description = '';
        },
        clearCategory(index) {
            this.entries[index].category_name = '';
        },
        clearSourceAccount(index) {
            this.entries[index].source_account = getAccount();
            this.detectTransactionType();
        },
        clearDestinationAccount(index) {
            this.entries[index].destination_account = getAccount();
            this.detectTransactionType();
        },

        init() {
            console.log('init()');
            this.addSplit();

            // load currencies and save in form data.
            loadCurrencies().then(data => {
                this.formStates.loadingCurrencies = false;
                this.formData.primaryCurrency = data.primaryCurrency;
                this.formData.defaultCurrency = data.defaultCurrency;
                this.formData.enabledCurrencies = data.enabledCurrencies;
                this.formData.primaryCurrencies = data.primaryCurrencies;
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
                console.log('Now in event listener "upload-success"');
                this.processUpload(event);
                document.querySelectorAll("input[type=file]").value = "";
            });

            document.addEventListener('upload-error', (event) => {
                console.log('Now in event listener "upload-error"')
                this.processUploadError(event);
            });
            document.addEventListener('upload-failed', (event) => {
                console.log('Now in event listener "upload-failed"')
                this.processUploadError(event);
            });
            // document.addEventListener('location-move', (event) => {
            //     this.entries[event.detail.index].latitude = event.detail.latitude;
            //     this.entries[event.detail.index].longitude = event.detail.longitude;
            // });
    //
    //         document.addEventListener('location-set', (event) => {
    //             this.entries[event.detail.index].hasLocation = true;
    //             this.entries[event.detail.index].latitude = event.detail.latitude;
    //             this.entries[event.detail.index].longitude = event.detail.longitude;
    //             this.entries[event.detail.index].zoomLevel = event.detail.zoomLevel;
    //         });
    //
    //         document.addEventListener('location-zoom', (event) => {
    //             this.entries[event.detail.index].hasLocation = true;
    //             this.entries[event.detail.index].zoomLevel = event.detail.zoomLevel;
    //         });
    //
    //
            // source can never be expense account
            this.filters.source = ['Asset account', 'Loan', 'Debt', 'Mortgage', 'Revenue account'];
            // destination can never be revenue account
            this.filters.destination = ['Expense account', 'Loan', 'Debt', 'Mortgage', 'Asset account'];
        },
        keyUpFromCategory(e) {
            if (e.key === 'Enter' && false === this.formStates.categorySelectVisible) {
                this.save();
                return;
            }
            this.formStates.categorySelectVisible = document.querySelector('input.ac-category').nextSibling.classList.contains('show');
        },
        save() {
            this.notifications.error.show = false;
            this.notifications.success.show = false;
            this.notifications.wait.show = false;
            this.formStates.isSubmitting = true;

            for (let i in this.entries) {
                    if (this.entries.hasOwnProperty(i)) {
                        this.entries[i].errors = defaultErrorSet();
                    }
                }

            // final check on transaction type.
            this.detectTransactionType();

            // parse transaction:
            let transactions = parseFromEntries(this.entries, null, this.groupProperties.transactionType);
            let submission = {
                group_title: this.groupProperties.title,
                fire_webhooks: this.formStates.webhooksButton,
                apply_rules: this.formStates.rulesButton,
                transactions: transactions
            };

            // catch for group title:
            // TODO later this must be handled with more care (ie use the group title input)
            if (transactions.length > 1) {
                submission.group_title = transactions[0].description;
            }

            // submit the transaction. Multi-stage process thing going on here!
            let poster = new Post();
            poster.post(submission).then((response) => {
                const group = response.data.data;
                // submission was a success!
                this.groupProperties.id = parseInt(group.id);
                this.groupProperties.title = group.attributes.group_title ?? group.attributes.transactions[0].description

                // process attachments, if any:
                const attachmentCount = processAttachments(this.groupProperties.id, group.attributes.transactions);

                if (attachmentCount > 0) {
                    // if count is more than zero, system is processing transactions in the background.
                    this.notifications.wait.show = true;
                    this.notifications.wait.text = i18next.t('firefly.wait_attachments');
                    return;
                }

                this.showMessageOrRedirectUser();
            }).catch((error) => {
                this.formStates.isSubmitting = true;
                if (typeof error.response !== 'undefined') {
                    this.parseErrors(error.response.data);
                }
            });
        },

        showMessageOrRedirectUser() {
            // disable all messages:
            this.notifications.error.show = false;
            this.notifications.success.show = false;
            this.notifications.wait.show = false;

            if (this.formStates.returnHereButton) {
                this.notifications.success.show = true;
                this.notifications.success.url = 'transactions/show/' + parseInt(this.groupProperties.id);
                this.notifications.success.text = i18next.t('firefly.stored_journal_js', {
                    description: this.groupProperties.title,
                    interpolation: {escapeValue: false}
                });
                this.formStates.isSubmitting = false;
                // reset group title again
                this.groupProperties.title = null;

                if (this.formStates.resetButton) {
                    this.entries = [];
                    this.addSplit();
                    this.groupProperties.totalAmount = 0;
                }
                return;
            }
            window.location = 'transactions/show/' + this.groupProperties.id + '?transaction_group_id=' + this.groupProperties.id + '&message=created';
        },

        parseErrors(data) {
            // disable all messages:
            this.notifications.error.show = true;
            this.notifications.success.show = false;
            this.notifications.wait.show = false;
            this.formStates.isSubmitting = false;
            this.notifications.error.text = i18next.t('firefly.errors_submission_v2', {errorMessage: data.message});

            if (data.hasOwnProperty('errors')) {
                this.entries = spliceErrorsIntoTransactions(data.errors, this.entries);
            }
        },

        addSplit() {
            console.log('addSplit()');
            this.entries.push(createEmptySplit());
            this.disableSplitAccounts();
            addAllAutocompleteToForm(this.filters);
        },

        removeSplit(index) {
            this.entries.splice(index, 1);
            // fall back to index 0
            const triggerFirstTabEl = document.querySelector('#split-0-tab')
            triggerFirstTabEl.click();
        },
    //
    //     clearLocation(e) {
    //         e.preventDefault();
    //         // remove location from entry, fire event, do nothing else (the map is somebody else's problem).
    //
    //         const target = e.currentTarget;
    //         const index = parseInt(target.attributes['data-index'].value);
    //         this.entries[index].hasLocation = false;
    //         this.entries[index].latitude = null;
    //         this.entries[index].longitude = null;
    //         this.entries[index].zoomLevel = null;
    //
    //         const removeEvent = new CustomEvent('location-remove', {
    //             detail: {
    //                 index: index
    //             }
    //         });
    //         document.dispatchEvent(removeEvent);
    //
    //         return false;
    //     },
    }
}

let comps = {create, sidebar, dates};

function loadPage() {
    Object.keys(comps).forEach(comp => {
        console.log(`Loading page component "${comp}"`);
        let data = comps[comp]();
        Alpine.data(comp, () => data);
    });
    Alpine.start();
}

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
