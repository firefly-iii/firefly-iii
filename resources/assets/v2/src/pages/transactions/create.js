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
import {createEmptySplit, defaultErrorSet} from "./shared/create-empty-split.js";
import {parseFromEntries} from "./shared/parse-from-entries.js";
import formatMoney from "../../util/format-money.js";
import Post from "../../api/v1/model/transaction/post.js";
import {loadCurrencies} from "./shared/load-currencies.js";
import {loadBudgets} from "./shared/load-budgets.js";
import {loadPiggyBanks} from "./shared/load-piggy-banks.js";
import {loadSubscriptions} from "./shared/load-subscriptions.js";

import 'leaflet/dist/leaflet.css';
import {addAutocomplete, getUrls} from "./shared/add-autocomplete.js";
import {
    changeCategory,
    changeDescription,
    changeDestinationAccount,
    changeSourceAccount,
    selectDestinationAccount,
    selectSourceAccount
} from "./shared/autocomplete-functions.js";
import {processAttachments} from "./shared/process-attachments.js";
import {spliceErrorsIntoTransactions} from "./shared/splice-errors-into-transactions.js";
import Tags from "bootstrap5-tags";
import {addLocation} from "./shared/manage-locations.js";
import i18next from "i18next";
// TODO upload attachments to other file
// TODO fix two maps, perhaps disconnect from entries entirely.
// TODO group title
// TODO map location from preferences
// TODO field preferences

const urls = getUrls();

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
            resetButton: false,
            rulesButton: true,
            webhooksButton: true,
            categorySelectVisible: false
        },

        // form behaviour during transaction
        formBehaviour: {
            formType: 'create',
            foreignCurrencyEnabled: true,
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
            if ('unknown' === sourceType && ['Asset account', 'Debt', 'Loan', 'Mortgage'].includes(destType)) {
                this.groupProperties.transactionType = 'deposit';
                console.log('Transaction type is detected to be "' + this.groupProperties.transactionType + '".');
                return;
            }
            if ('Expense account' === sourceType && ['Asset account', 'Debt', 'Loan', 'Mortgage'].includes(destType)) {
                this.groupProperties.transactionType = 'deposit';
                console.warn('FORCE transaction type to be "' + this.groupProperties.transactionType + '".');
                this.entries[0].source_account.id = '';
                return;
            }
            if (['Debt', 'Loan', 'Mortgage'].includes(sourceType) && 'Asset account' === destType) {
                this.groupProperties.transactionType = 'deposit';
                console.log('Transaction type is detected to be "' + this.groupProperties.transactionType + '".');
                return;
            }
            console.warn('Unknown account combination between "' + sourceType + '" and "' + destType + '".');
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

        filterNativeCurrencies(code) {
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
            this.formData.nativeCurrencies = list;

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


        },

        processUpload(event) {
            this.showMessageOrRedirectUser();
        },

        processUploadError(event) {
            this.notifications.success.show = false;
            this.notifications.wait.show = false;
            this.notifications.error.show = true;
            this.formStates.isSubmitting = false;
            this.notifications.error.text = i18next.t('firefly.errors_upload');
            console.error(event);
        },

        init() {
            this.addSplit();

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
                document.querySelectorAll("input[type=file]").value = "";
            });

            document.addEventListener('upload-error', (event) => {
                this.processUploadError(event);
            });
            document.addEventListener('location-move', (event) => {
                this.entries[event.detail.index].latitude = event.detail.latitude;
                this.entries[event.detail.index].longitude = event.detail.longitude;
            });

            document.addEventListener('location-set', (event) => {
                this.entries[event.detail.index].hasLocation = true;
                this.entries[event.detail.index].latitude = event.detail.latitude;
                this.entries[event.detail.index].longitude = event.detail.longitude;
                this.entries[event.detail.index].zoomLevel = event.detail.zoomLevel;
            });

            document.addEventListener('location-zoom', (event) => {
                this.entries[event.detail.index].hasLocation = true;
                this.entries[event.detail.index].zoomLevel = event.detail.zoomLevel;
            });


            // source can never be expense account
            this.filters.source = ['Asset account', 'Loan', 'Debt', 'Mortgage', 'Revenue account'];
            // destination can never be revenue account
            this.filters.destination = ['Expense account', 'Loan', 'Debt', 'Mortgage', 'Asset account'];
        },
        keyUpFromCategory(e) {
            if (e.key === 'Enter' && false === this.formStates.categorySelectVisible) {
                this.submitTransaction();
                return;
            }
            this.formStates.categorySelectVisible = document.querySelector('input.ac-category').nextSibling.classList.contains('show');
        },
        submitTransaction() {
            // reset all messages:
            this.notifications.error.show = false;
            this.notifications.success.show = false;
            this.notifications.wait.show = false;

            // reset all errors in the entries array:
            for (let i in this.entries) {
                if (this.entries.hasOwnProperty(i)) {
                    this.entries[i].errors = defaultErrorSet();
                }
            }

            // form is now submitting:
            this.formStates.isSubmitting = true;

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
            console.log(submission);
            poster.post(submission).then((response) => {
                const group = response.data.data;
                // submission was a success!
                this.groupProperties.id = parseInt(group.id);
                this.groupProperties.title = group.attributes.group_title ?? group.attributes.transactions[0].description
                console.log('group title is now: ', this.groupProperties.title);

                // process attachments, if any:
                const attachmentCount = processAttachments(this.groupProperties.id, group.attributes.transactions);

                if (attachmentCount > 0) {
                    // if count is more than zero, system is processing transactions in the background.
                    this.notifications.wait.show = true;
                    this.notifications.wait.text = i18next.t('firefly.wait_attachments');
                    return;
                }

                // if not, respond to user options:
                this.showMessageOrRedirectUser();
            }).catch((error) => {

                this.submitting = false;
                console.log(error);
                // todo put errors in form
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
                this.notifications.success.url = 'transactions/show/' + this.groupProperties.id;
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
            this.entries.push(createEmptySplit());

            setTimeout(() => {
                // render tags:
                Tags.init('select.ac-tags', {
                    allowClear: true,
                    server: urls.tag,
                    liveServer: true,
                    clearEnd: true,
                    labelField: 'title',
                    valueField: 'id',
                    queryParam: 'filter[query]',
                    allowNew: true,
                    serverDataKey: 'data',
                    notFoundMessage: i18next.t('firefly.nothing_found'),
                    noCache: true,
                    fetchOptions: {
                        headers: {
                            'X-CSRF-TOKEN': document.head.querySelector('meta[name="csrf-token"]').content
                        }
                    }
                });
                const count = this.entries.length - 1;
                // if(document.querySelector('#location_map_' + count)) { }
                addLocation(count);

                // addedSplit, is called from the HTML
                // for source account
                const renderAccount = function (item, b, c) {
                    return item.name_with_balance + '<br><small class="text-muted">' + i18next.t('firefly.account_type_' + item.type) + '</small>';
                };
                console.log('here we are in');
                addAutocomplete({
                    selector: 'input.ac-source',
                    serverUrl: urls.account,
                    onRenderItem: renderAccount,
                    valueField: 'id',
                    labelField: 'name_with_balance',
                    onChange: changeSourceAccount,
                    onSelectItem: selectSourceAccount,
                    hiddenValue: this.entries[count].source_account.alpine_name
                });
                addAutocomplete({
                    selector: 'input.ac-dest',
                    serverUrl: urls.account,
                    account_types: this.filters.destination,
                    valueField: 'id',
                    labelField: 'name_with_balance',
                    onRenderItem: renderAccount,
                    onChange: changeDestinationAccount,
                    onSelectItem: selectDestinationAccount
                });
                addAutocomplete({
                    selector: 'input.ac-category',
                    serverUrl: urls.category,
                    valueField: 'id',
                    labelField: 'title',
                    onChange: changeCategory,
                    onSelectItem: changeCategory
                });
                addAutocomplete({
                    selector: 'input.ac-description',
                    serverUrl: urls.description,
                    valueField: 'id',
                    labelField: 'title',
                    onChange: changeDescription,
                    onSelectItem: changeDescription,
                });

            }, 150);
        },

        removeSplit(index) {
            this.entries.splice(index, 1);
            // fall back to index 0
            const triggerFirstTabEl = document.querySelector('#split-0-tab')
            triggerFirstTabEl.click();
        },

        clearLocation(e) {
            e.preventDefault();
            // remove location from entry, fire event, do nothing else (the map is somebody else's problem).

            const target = e.currentTarget;
            const index = parseInt(target.attributes['data-index'].value);
            this.entries[index].hasLocation = false;
            this.entries[index].latitude = null;
            this.entries[index].longitude = null;
            this.entries[index].zoomLevel = null;

            const removeEvent = new CustomEvent('location-remove', {
                detail: {
                    index: index
                }
            });
            document.dispatchEvent(removeEvent);

            return false;
        },
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
