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
import formatMoney from "../../util/format-money.js";
import Get from "../../api/v1/model/transaction/get.js";
import {parseDownloadedSplits} from "./shared/parse-downloaded-splits.js";
import {addAutocomplete, getUrls} from "./shared/add-autocomplete.js";
import {
    changeCategory,
    changeDescription,
    changeDestinationAccount,
    changeSourceAccount,
    selectDestinationAccount,
    selectSourceAccount
} from "./shared/autocomplete-functions.js";
import {loadCurrencies} from "./shared/load-currencies.js";
import {loadBudgets} from "./shared/load-budgets.js";
import {loadPiggyBanks} from "./shared/load-piggy-banks.js";
import {loadSubscriptions} from "./shared/load-subscriptions.js";
import Tags from "bootstrap5-tags";
import i18next from "i18next";
import {defaultErrorSet} from "./shared/create-empty-split.js";
import {parseFromEntries} from "./shared/parse-from-entries.js";
import Put from "../../api/v1/model/transaction/put.js";
import {processAttachments} from "./shared/process-attachments.js";
import {spliceErrorsIntoTransactions} from "./shared/splice-errors-into-transactions.js";

// TODO upload attachments to other file
// TODO fix two maps, perhaps disconnect from entries entirely.
// TODO group title
// TODO map location from preferences
// TODO field preferences
// TODO filters
// TODO parse amount

const urls = getUrls();

let transactions = function () {
    return {
        // transactions are stored in "entries":
        entries: [],
        originals: [],

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
            formType: 'edit',
            foreignCurrencyEnabled: true,
            categorySelectVisible: false,
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

        keyUpFromCategory(e) {
            if (e.key === 'Enter' && false === this.formBehaviour.categorySelectVisible) {
                this.submitTransaction();
                return;
            }
            this.formBehaviour.categorySelectVisible = document.querySelector('input.ac-category').nextSibling.classList.contains('show');
        },

        // submit the transaction form.
        // TODO pretty much duplicate of create.js
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

            // parse transaction:
            let transactions = parseFromEntries(this.entries, this.originals, this.groupProperties.transactionType);
            let submission = {
                group_title: this.groupProperties.editTitle,
                fire_webhooks: this.formStates.webhooksButton,
                apply_rules: this.formStates.rulesButton,
                transactions: transactions
            };


            // catch for group title:
            if (null === this.groupProperties.title && transactions.length > 1) {
                submission.group_title = transactions[0].description;
            }

            // submit the transaction. Multi-stage process thing going on here!
            let putter = new Put();
            console.log(submission);
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

                this.submitting = false;
                console.log(error);
                // todo put errors in form
                if (typeof error.response !== 'undefined') {
                    this.parseErrors(error.response.data);
                }


            });


        },
        // part of the account selection auto-complete
        filters: {
            source: [], destination: [],
        },

        addedSplit() {
            setTimeout(() => {
                // addedSplit, is called from the HTML
                // for source account
                const renderAccount = function (item, b, c) {
                    console.log(item);
                    return item.title + '<br><small class="text-muted">' + i18next.t('firefly.account_type_' + item.meta.type) + '</small>';
                };
                addAutocomplete({
                    selector: 'input.ac-source',
                    serverUrl: urls.account,
                    account_types: this.filters.source,
                    onRenderItem: renderAccount,
                    valueField: 'id',
                    labelField: 'title',
                    onChange: changeSourceAccount,
                    onSelectItem: selectSourceAccount
                });
                addAutocomplete({
                    selector: 'input.ac-dest',
                    serverUrl: urls.account,
                    valueField: 'id',
                    labelField: 'title',
                    account_types: this.filters.destination,
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
            }, 250);

        },

        // events in the form
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

        // duplicate function but this is easier.
        formattedTotalAmount() {
            if (this.entries.length === 0) {
                return formatMoney(this.groupProperties.totalAmount, 'EUR');
            }
            return formatMoney(this.groupProperties.totalAmount, this.entries[0].currency_code ?? 'EUR');
        },
        getTags(index) {
            console.log('at get tags ' + index);
            console.log(this.entries[index].tags);
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
                this.groupProperties.title = data.attributes.title ?? data.attributes.transactions[0].description;
                this.entries = parseDownloadedSplits(data.attributes.transactions, parseInt(data.id));

                // remove waiting thing.
                this.notifications.wait.show = false;
            }).then(() => {
                this.groupProperties.totalAmount = 0;
                for (let i in this.entries) {
                    if (this.entries.hasOwnProperty(i)) {
                        this.groupProperties.totalAmount = this.groupProperties.totalAmount + parseFloat(this.entries[i].amount);
                        // TODO this does not include all possible types.
                        this.filters.source.push(this.entries[i].source_account.type);
                        this.filters.destination.push(this.entries[i].destination_account.type);
                    }
                }
                console.log(this.filters);
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
            // download translations and get the transaction group.
            this.notifications.wait.show = true;
            this.notifications.wait.text = i18next.t('firefly.wait_loading_transaction');
            this.getTransactionGroup();

            // load meta data.
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

            // add some event listeners
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
        // TODO is a duplicate
        showMessageOrRedirectUser() {
            // disable all messages:
            this.notifications.error.show = false;
            this.notifications.success.show = false;
            this.notifications.wait.show = false;

            if (this.formStates.returnHereButton) {
                this.notifications.success.show = true;
                this.notifications.success.url = 'transactions/show/' + this.groupProperties.id;
                this.notifications.success.text = i18next.t('firefly.updated_journal_js', {description: this.groupProperties.title});
                return;
            }
            window.location = 'transactions/show/' + this.groupProperties.id + '?transaction_group_id=' + this.groupProperties.id + '&message=updated';
        },
        // TODO is a duplicate
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
        // TODO is a duplicate
        processUpload(event) {
            this.showMessageOrRedirectUser();
        },
        // TODO is a duplicate
        processUploadError(event) {
            this.notifications.success.show = false;
            this.notifications.wait.show = false;
            this.notifications.error.show = true;
            this.formStates.isSubmitting = false;
            this.notifications.error.text = i18next.t('firefly.errors_upload');
            console.error(event);
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
