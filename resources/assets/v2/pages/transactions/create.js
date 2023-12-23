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
import Get from "../../api/v2/model/currency/get.js";
import {getVariable} from "../../store/get-variable.js";
import {I18n} from "i18n-js";
import {loadTranslations} from "../../support/load-translations.js";

let i18n;

const urls = {
    description: '/api/v2/autocomplete/transaction-descriptions',
    account: '/api/v2/autocomplete/accounts',
};

let transactions = function () {
    return {
        count: 0,
        totalAmount: 0,
        transactionType: 'unknown',
        showSuccessMessage: false,
        showErrorMessage: false,
        entries: [],
        loadingCurrencies: true,
        defaultCurrency: {},
        enabledCurrencies: [],
        nativeCurrencies: [],
        foreignCurrencies: [],
        filters: {
            source: [],
            destination: [],
        },
        errorMessageText: '',
        successMessageLink: '#',
        successMessageText: '',

        // four buttons
        returnHereButton: false,
        resetButton: false,
        resetButtonEnabled: false,
        rulesButton: true,
        webhookButton: true,

        // state of the form
        submitting: false,


        detectTransactionType() {
            const sourceType = this.entries[0].source_account.type ?? 'unknown';
            const destType = this.entries[0].destination_account.type ?? 'unknown';
            if ('unknown' === sourceType && 'unknown' === destType) {
                this.transactionType = 'unknown';
                console.warn('Cannot infer transaction type from two unknown accounts.');
                return;
            }
            // transfer: both are the same and in strict set of account types
            if (sourceType === destType && ['Asset account', 'Loan', 'Debt', 'Mortgage'].includes(sourceType)) {
                this.transactionType = 'transfer';
                console.log('Transaction type is detected to be "' + this.transactionType + '".');

                // this also locks the amount into the amount of the source account
                // and the foreign amount (if different) in that of the destination account.
                console.log('filter down currencies for transfer.');

                return;
            }
            // withdrawals:
            if ('Asset account' === sourceType && ['Expense account', 'Debt', 'Loan', 'Mortgage'].includes(destType)) {
                this.transactionType = 'withdrawal';
                console.log('[a] Transaction type is detected to be "' + this.transactionType + '".');
                this.filterNativeCurrencies(this.entries[0].source_account.currency_code);
                return;
            }
            if ('Asset account' === sourceType && 'unknown' === destType) {
                this.transactionType = 'withdrawal';
                console.log('[b] Transaction type is detected to be "' + this.transactionType + '".');
                console.log(this.entries[0].source_account);
                this.filterNativeCurrencies(this.entries[0].source_account.currency_code);
                return;
            }
            if (['Debt', 'Loan', 'Mortgage'].includes(sourceType) && 'Expense account' === destType) {
                this.transactionType = 'withdrawal';
                console.log('[c] Transaction type is detected to be "' + this.transactionType + '".');
                this.filterNativeCurrencies(this.entries[0].source_account.currency_code);
                return;
            }

            // deposits:
            if ('Revenue account' === sourceType && ['Asset account', 'Debt', 'Loan', 'Mortgage'].includes(destType)) {
                this.transactionType = 'deposit';
                console.log('Transaction type is detected to be "' + this.transactionType + '".');
                return;
            }
            if (['Debt', 'Loan', 'Mortgage'].includes(sourceType) && 'Asset account' === destType) {
                this.transactionType = 'deposit';
                console.log('Transaction type is detected to be "' + this.transactionType + '".');
                return;
            }
            console.warn('Unknown account combination between "' + sourceType + '" and "' + destType + '".');
        },
        selectSourceAccount(item, ac) {
            const index = parseInt(ac._searchInput.attributes['data-index'].value);
            document.querySelector('#form')._x_dataStack[0].$data.entries[index].source_account =
                {
                    id: item.id,
                    name: item.name,
                    alpine_name: item.name,
                    type: item.type,
                    currency_code: item.currency_code,
                };
            console.log('Changed source account into a known ' + item.type.toLowerCase());
            document.querySelector('#form')._x_dataStack[0].detectTransactionType();
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
            document.querySelector('#form')._x_dataStack[0].$data.entries[index].destination_account =
                {
                    id: item.id,
                    name: item.name,
                    alpine_name: item.name,
                    type: item.type,
                    currency_code: item.currency_code,
                };
            console.log('Changed destination account into a known ' + item.type.toLowerCase());
            document.querySelector('#form')._x_dataStack[0].detectTransactionType();
        },
        loadCurrencies() {
            console.log('Loading user currencies.');
            let params = {
                page: 1,
                limit: 1337
            };
            let getter = new Get();
            getter.list({}).then((response) => {
                for (let i in response.data.data) {
                    if (response.data.data.hasOwnProperty(i)) {
                        let current = response.data.data[i];
                        if (current.attributes.enabled) {
                            let obj =

                                {
                                    id: current.id,
                                    name: current.attributes.name,
                                    code: current.attributes.code,
                                    default: current.attributes.default,
                                    symbol: current.attributes.symbol,
                                    decimal_places: current.attributes.decimal_places,

                                };
                            if (obj.default) {
                                this.defaultCurrency = obj;
                            }
                            this.enabledCurrencies.push(obj);
                            this.nativeCurrencies.push(obj);
                        }
                    }
                }
                this.loadingCurrencies = false;
                console.log(this.enabledCurrencies);
            });
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
                document.querySelector('#form')._x_dataStack[0].$data.entries[index].source_account =
                    {
                        name: ac._searchInput.value,
                        alpine_name: ac._searchInput.value,
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
                document.querySelector('#form')._x_dataStack[0].$data.entries[index].destination_account =
                    {
                        name: ac._searchInput.value,
                        alpine_name: ac._searchInput.value,
                    };
                console.log('Changed destination account into a unknown account called "' + ac._searchInput.value + '"');
                document.querySelector('#form')._x_dataStack[0].detectTransactionType();
            }
        },


        // error and success messages:
        showError: false,
        showSuccess: false,

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
                onSelectItem: console.log,
            });


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
            this.loadCurrencies();

            // source can never be expense account
            this.filters.source = ['Asset account', 'Loan', 'Debt', 'Mortgage', 'Revenue account'];
            // destination can never be revenue account
            this.filters.destination = ['Expense account', 'Loan', 'Debt', 'Mortgage', 'Asset account'];
        },
        submitTransaction() {
            this.submitting = true;
            this.showSuccessMessage = false;
            this.showErrorMessage = false;
            this.detectTransactionType();

            let transactions = parseFromEntries(this.entries, this.transactionType);
            let submission = {
                // todo process all options
                group_title: null,
                fire_webhooks: false,
                apply_rules: false,
                transactions: transactions
            };
            if (transactions.length > 1) {
                // todo improve me
                submission.group_title = transactions[0].description;
            }
            let poster = new Post();
            console.log(submission);
            poster.post(submission).then((response) => {
                this.submitting = false;
                console.log(response);
                const id = parseInt(response.data.data.id);
                if (this.returnHereButton) {
                    // todo create success banner
                    this.showSuccessMessage = true;
                    this.successMessageLink = 'transactions/show/' + id;
                    this.successMessageText = i18n.t('firefly.stored_journal_js', {description: submission.group_title ?? submission.transactions[0].description});
                    // todo clear out form if necessary
                    if(this.resetButton) {
                        this.entries = [];
                        this.addSplit();
                        this.totalAmount = 0;
                    }
                }

                if (!this.returnHereButton) {
                    window.location = 'transactions/show/' + id + '?transaction_group_id=' + id + '&message=created';
                }

            }).catch((error) => {
                this.submitting = false;
                // todo put errors in form
                this.parseErrors(error.response.data);


            });
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

            for (const key in data.errors) {
                if (data.errors.hasOwnProperty(key)) {
                    if (key === 'group_title') {
                        // todo handle group errors.
                        //this.group_title_errors = errors.errors[key];
                    }
                    if (key !== 'group_title') {
                        // lol, the dumbest way to explode "transactions.0.something" ever.
                        transactionIndex = parseInt(key.split('.')[1]);
                        fieldName = key.split('.')[2];
                        // set error in this object thing.
                        switch (fieldName) {
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
                                this.entries[transactionIndex].errors.source_account =
                                    this.entries[transactionIndex].errors.source_account.concat(data.errors[key]);
                                break;
                            case 'destination_name':
                            case 'destination_id':
                                this.entries[transactionIndex].errors.destination_account =
                                    this.entries[transactionIndex].errors.destination_account.concat(data.errors[key]);
                                break;
                            case 'foreign_amount':
                            case 'foreign_currency_id':
                                this.entries[transactionIndex].errors.foreign_amount =
                                    this.entries[transactionIndex].errors.foreign_amount.concat(data.errors[key]);
                                break;
                        }
                    }
                    // unique some things
                    if (typeof this.entries[transactionIndex] !== 'undefined') {
                        this.entries[transactionIndex].errors.source_account =
                            Array.from(new Set(this.entries[transactionIndex].errors.source_account));
                        this.entries[transactionIndex].errors.destination_account =
                            Array.from(new Set(this.entries[transactionIndex].errors.destination_account));
                    }
                }
            }
            console.log(this.entries[0].errors);
        },
        setDefaultErrors() {

        },
        addSplit() {
            this.entries.push(createEmptySplit());
        },
        removeSplit(index) {
            this.entries.splice(index, 1);
            // fall back to index 0
            const triggerFirstTabEl = document.querySelector('#split-0-tab')
            triggerFirstTabEl.click();
        },
        formattedTotalAmount() {
            return formatMoney(this.totalAmount, 'EUR');
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
