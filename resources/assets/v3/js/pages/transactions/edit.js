/*
 * edit.js
 * Copyright (c) 2024 james@firefly-iii.org
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
import Get from "../../api/model/transaction/get.js";
import {parseDownloadedSplits} from "./shared/parse-downloaded-splits.js";
import {addAllAutocompleteToForm, getUrls} from "./shared/add-autocomplete.js";
import {loadCurrencies} from "./shared/load-currencies.js";
import {loadBudgets} from "./shared/load-budgets.js";
import {loadPiggyBanks} from "./shared/load-piggy-banks.js";
import {processUploadError} from "./shared/process-upload-error.js";
import {loadSubscriptions} from "./shared/load-subscriptions.js";
import Tags from "bootstrap5-tags";
import i18next from "i18next";
import {createEmptySplit, defaultErrorSet} from "./shared/create-empty-split.js";
import {parseFromEntries} from "./shared/parse-from-entries.js";
import Put from "../../api/model/transaction/put.js";
import {showMessageOrRedirectUser} from "./shared/show-message-or-redirect.js";
import {processAttachments} from "./shared/process-attachments.js";
import sidebar from "../shared/sidebar.js";
import {disableSplitAccounts} from "./shared/disable-split-accounts.js";
import {parseTotalAmount} from "./shared/parse-total-amount.js";
import {keyUpFromCategory} from "./shared/keyup-from-category.js";
import {changedAmount} from "./shared/changed-amount.js";
import {changedForeignAmount} from "./shared/changed-foreign-amount.js";
import {parseErrors} from "./shared/parse-errors.js";
import {addSplit} from "./shared/add-split.js";
import {clearDestinationAccount, clearSourceAccount} from "./shared/clear-fields.js";
import {detectTransactionType} from "./shared/detect-transaction-type.js";
import {determineAmountCurrency} from "./shared/determine-amount-currency.js";
import {loadCustomFields} from './shared/load-custom-fields.js';
import {displayMap} from "./shared/display-map.js";
import {loadDefaultCoordinates} from './shared/load-default-coordinates.js';
import {renderMap} from './shared/render-map.js';
import 'leaflet/dist/leaflet.css';
import {onMapClick} from './shared/on-map-click.js';
import {onMapZoom} from './shared/on-map-zoom.js';
import {clearLocation} from './shared/clear-location.js';
import {removeSplit} from "./shared/remove-split.js";

const urls = getUrls();

let transactions = function () {
    return {
        // needed for translations
        i18next: null,

        // transactions are stored in "entries":
        entries: [],
        originals: [],
        links: [],

        // maps are stored in this array so they can be referred to.
        maps: [],
        markers: [],

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

        // form behavior during transaction
        formBehaviour: {
            formType: 'edit',
            foreignCurrencyEnabled: true,
            categorySelectVisible: false,
            customFields: {},
            defaultCoordinates: {
                loaded: false,
                latitude: 30,
                longitude: 20,
                zoom_level: 9,
            },
        },

        // form data (except transactions) is stored in formData
        formData: {
            primaryCurrency: null,
            amountCurrency: null, // this is the currency of the amount field/**/
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
            titleErrors: [],
            title: null,
            editTitle: null,
            id: null, totalAmount: 0,
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

        // shared functions between edit/create
        parseTotalAmount: parseTotalAmount,
        disableSplitAccounts: disableSplitAccounts,
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
        displayMap: displayMap,
        loadDefaultCoordinates: loadDefaultCoordinates,
        renderMap: renderMap,
        onMapClick: onMapClick,
        onMapZoom: onMapZoom,
        clearLocation: clearLocation,
        removeSplit: removeSplit,


        // part of the account selection auto-complete

        changedDateTime(event) {
            console.warn('changedDateTime, event is not used');
        },

        changedDescription(event) {
            console.warn('changedDescription, event is not used');
        },

        changedDestinationAccount(event) {
            console.warn('changedDestinationAccount, event is not used');
        },

        changedSourceAccount(event) {
            console.warn('changedSourceAccount, event is not used');
        },

        getTags(index) {
            return this.entries[index].tags ?? [];
        },

        getTransactionGroup() {
            this.entries = [];
            const page = window.location.href.split('/');
            const groupId = parseInt(page[page.length - 1]);
            const getter = new Get();
            getter.show(groupId, {}).then((response) => {
                const data = response.data.data;
                this.groupProperties.id = parseInt(data.id);
                this.groupProperties.transactionType = data.attributes.transactions[0].type.toLowerCase();
                this.groupProperties.title = data.attributes.group_title ?? data.attributes.transactions[0].description;
                this.entries = parseDownloadedSplits(data.attributes.transactions, parseInt(data.id));

                // set amountCurrency.
                for(let i in this.formData.enabledCurrencies) {
                    if(this.formData.enabledCurrencies.hasOwnProperty(i)) {
                        if(this.formData.enabledCurrencies[i].code === this.entries[0].currency_code) {
                            this.formData.amountCurrency = this.formData.enabledCurrencies[i];
                            console.log('selected ' + this.formData.amountCurrency.code + ' as amount currency.');
                        }
                    }
                }
                // limit foreignCurrencies to a single one when it's a transfer
                if('transfer' === this.groupProperties.transactionType) {
                    this.formData.foreignCurrencies = [];
                    for(let i in this.formData.enabledCurrencies) {
                        if(this.formData.enabledCurrencies.hasOwnProperty(i)) {
                            if(this.formData.enabledCurrencies[i].code === this.entries[0].forein_currency_code) {
                                this.formData.foreignCurrencies.push(this.formData.enabledCurrencies[i]);
                            }
                        }
                    }
                }

                // remove waiting thing.
                this.notifications.wait.show = false;
            }).then(() => {
                this.groupProperties.totalAmount = 0;
                for (let i in this.entries) {
                    if (this.entries.hasOwnProperty(i)) {
                        this.groupProperties.totalAmount = this.groupProperties.totalAmount + parseFloat(this.entries[i].amount);
                    }
                }
                setTimeout(() => {
                    // render tags:
                    Tags.init('select.ac-tags', {
                        allowClear: true,
                        server: urls.tag,
                        liveServer: true,
                        clearEnd: true,
                        allowNew: true,
                        labelField: 'title',
                        valueField: 'id',
                        queryParam: 'filter[query]',
                        notFoundMessage: i18next.t('firefly.nothing_found'),
                        noCache: true,
                        fetchOptions: {
                            headers: {
                                'X-CSRF-TOKEN': document.head.querySelector('meta[name="csrf-token"]').content
                            }
                        }
                    });
                }, 150);
            });
        },

        init() {
            console.log('Init()');

            // load custom field preference and enable/disable those fields.
            this.loadCustomFields().then(data => {
                console.log('Loaded custom fields');
                this.formBehaviour.customFields = data;
            });

            this.i18next = i18next;
            // download translations and get the transaction group.
            this.notifications.wait.show = true;
            this.notifications.wait.text = i18next.t('firefly.wait_loading_transaction');

            // load meta data.
            loadCurrencies().then(data => {
                this.formStates.loadingCurrencies = false;
                this.formData.primaryCurrency = data.primaryCurrency;
                this.formData.enabledCurrencies = data.enabledCurrencies;
                this.formData.primaryCurrencies = data.primaryCurrencies;
                this.formData.foreignCurrencies = data.foreignCurrencies;
                this.getTransactionGroup();
            });

            loadBudgets(true).then(data => {
                this.formData.budgets = data;
                this.formStates.loadingBudgets = false;
            });
            loadPiggyBanks().then(data => {
                this.formData.piggyBanks = data;
                this.formStates.loadingPiggyBanks = false;
            });
            loadSubscriptions(true).then(data => {
                this.formData.subscriptions = data;
                this.formStates.loadingSubscriptions = false;
            });

            // add some event listeners
            document.addEventListener('upload-success', (event) => {
                this.processUpload(event);
                document.querySelectorAll("input[type=file]").value = "";
            });

            document.addEventListener('upload-error', (event) => {
                this.processUploadError(event);
            });
            // document.addEventListener('location-move', (event) => {
            //     this.entries[event.detail.index].latitude = event.detail.latitude;
            //     this.entries[event.detail.index].longitude = event.detail.longitude;
            // });

            // document.addEventListener('location-set', (event) => {
            //     this.entries[event.detail.index].hasLocation = true;
            //     this.entries[event.detail.index].latitude = event.detail.latitude;
            //     this.entries[event.detail.index].longitude = event.detail.longitude;
            //     this.entries[event.detail.index].zoom_level = event.detail.zoomLevel;
            // });

            // document.addEventListener('location-zoom', (event) => {
            //     console.log('this happens?');
            //     this.entries[event.detail.index].hasLocation = true;
            //     this.entries[event.detail.index].zoom_level = event.detail.zoomLevel;
            // });
        },


        // TODO is a duplicate
        processUpload(event) {
            this.showMessageOrRedirectUser();
        },

        // submit the transaction form.
        // basically the same as store.js.
        save() {
            this.notifications.error.show = false;
            this.notifications.success.show = false;
            this.notifications.wait.show = false;
            this.formStates.isSubmitting = true;

            // reset all errors in the entries array:
            for (let i in this.entries) {
                if (this.entries.hasOwnProperty(i)) {
                    this.entries[i].errors = defaultErrorSet();
                }
            }
            // parse transaction:
            let transactions = parseFromEntries(this.entries, this.originals, this.groupProperties.transactionType);
            let submission = {
                group_title: this.groupProperties.title,
                fire_webhooks: this.formStates.webhooksButton,
                apply_rules: this.formStates.rulesButton,
                transactions: transactions
            };

            // catch for group title:
            if (null === this.groupProperties.title && transactions.length > 1) {
                submission.group_title = transactions[0].description;
            }
            if(1 === transactions.length) {
                submission.group_title = null;
            }

            // submit the transaction. Multi-stage process thing going on here!
            let putter = new Put();
            putter.put(submission, {id: this.groupProperties.id}).then((response) => {
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

                // if not, respond to user options:
                this.showMessageOrRedirectUser();
            }).catch((error) => {
                this.formStates.isSubmitting = false;
                if (typeof error.response !== 'undefined') {
                    this.parseErrors(error.response.data);
                }
            });
        },

        // exclusive to edit form, used to initialize splits.
        addedSplit() {
            console.log('addedSplit()');
            this.disableSplitAccounts();
            this.addAllAutocompleteToForm();
        },

    }
}

let comps = {transactions, sidebar, dates};


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
