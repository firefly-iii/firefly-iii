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
import {disableSplitAccounts} from './shared/disable-split-accounts.js';
import {parseTotalAmount} from "./shared/parse-total-amount.js";
import {keyUpFromCategory} from "./shared/keyup-from-category.js";
import {changedAmount} from "./shared/changed-amount.js";
import {changedForeignAmount} from "./shared/changed-foreign-amount.js";
import {parseErrors} from "./shared/parse-errors.js";
// import {addLocation} from "./shared/manage-locations.js";
import i18next from "i18next";
import {processUploadError} from "./shared/process-upload-error.js";
import {showMessageOrRedirectUser} from "./shared/show-message-or-redirect.js";
import {addSplit} from "./shared/add-split.js";
import {clearSourceAccount, clearDestinationAccount} from './shared/clear-fields.js';
import {detectTransactionType} from './shared/detect-transaction-type.js';
import {determineAmountCurrency} from './shared/determine-amount-currency.js';
import {loadCustomFields} from './shared/load-custom-fields.js';
// TODO fix two maps, perhaps disconnect from entries entirely.
// TODO map location from preferences
// TODO field preferences

const urls = getUrls();

let create = function () {
    return {
        // needed for translations.
        i18next: null,

        // transactions are stored in "entries":
        entries: [],

        // properties for the entire transaction group
        groupProperties: {
            transactionType: 'unknown',
            titleErrors: [],
            title: null,
            id: null,
            totalAmount: 0,
        },

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

        // form behavior during transaction, for shared components.
        formBehaviour: {
            formType: 'create',
            foreignCurrencyEnabled: true,
            customFields: {},
        },

        // form data (except transactions) is stored in formData
        formData: {
            // primaryCurrency: null,
            // defaultCurrency: null,
            amountCurrency: null, // this is the currency of the amount field
            enabledCurrencies: [],
            primaryCurrencies: [], // TODO this list is not being used.
            foreignCurrencies: [], // this is the select list for foreign currencies.
            budgets: [],
            piggyBanks: [],
            subscriptions: [],
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
        // shared functions with edit/create transaction.
        disableSplitAccounts: disableSplitAccounts,
        parseTotalAmount: parseTotalAmount,
        processUploadError: processUploadError,
        keyUpFromCategory: keyUpFromCategory,
        changedAmount: changedAmount,
        changedForeignAmount: changedForeignAmount,
        showMessageOrRedirectUser: showMessageOrRedirectUser,
        parseErrors: parseErrors,
        addSplit:addSplit,
        clearSourceAccount: clearSourceAccount,
        clearDestinationAccount: clearDestinationAccount,
        detectTransactionType: detectTransactionType,
        determineAmountCurrency: determineAmountCurrency,
        addAllAutocompleteToForm: addAllAutocompleteToForm,
        loadCustomFields: loadCustomFields,


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




        addedSplit() {
            this.addAllAutocompleteToForm();
        },

        processUpload(event) {
            console.log('Now in processUpload()');
            this.showMessageOrRedirectUser();
        },
        clearDescription(index) {
            this.entries[index].description = '';
        },
        clearCategory(index) {
            this.entries[index].category_name = '';
        },


        init() {
            console.log('init()');
            this.i18next = i18next;
            this.addSplit();

            // load custom field preference and enable/disable those fields.
            this.loadCustomFields().then(data => {
                console.log('Loaded custom fields', data);
                this.formBehaviour.customFields = data;
            });

            // load currencies and save in form data.
            loadCurrencies().then(data => {
                this.formStates.loadingCurrencies = false;
                this.formData.amountCurrency = data.primaryCurrency;
                this.formData.enabledCurrencies = data.enabledCurrencies;
                this.formData.primaryCurrencies = data.primaryCurrencies;
                this.formData.foreignCurrencies = data.foreignCurrencies;
            });

            loadBudgets(false).then(data => {
                this.formData.budgets = data;
                this.formStates.loadingBudgets = false;
            });
            loadPiggyBanks().then(data => {
                this.formData.piggyBanks = data;
                this.formStates.loadingPiggyBanks = false;
            });
            loadSubscriptions(false).then(data => {
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
            if (transactions.length > 1 && ('' === submission.group_title || null === submission.group_title)) {
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
