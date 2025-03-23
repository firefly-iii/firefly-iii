/*
 * budgets.js
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
import {getVariable} from "../../store/get-variable.js";
import Get from "../../api/v1/model/transaction/get.js";
import {getDefaultChartSettings} from "../../support/default-chart-settings.js";
import {Chart} from 'chart.js';
import {Flow, SankeyController} from 'chartjs-chart-sankey';
import {getCacheKey} from "../../support/get-cache-key.js";
import {format} from "date-fns";
import i18next from "i18next";

Chart.register({SankeyController, Flow});

const SANKEY_CACHE_KEY = 'ds_sankey_data';
let currencies = [];
let afterPromises = false;
let chart = null;
let transactions = [];
let autoConversion = false;
let translations = {
    category: null,
    unknown_category: null,
    in: null,
    out: null,
    // TODO
    unknown_source: null,
    unknown_dest: null,
    unknown_account: null,
    expense_account: null,
    revenue_account: null,
    budget: null,
    unknown_budget: null,
    all_money: null,
};

const colors = {
    a: 'red',
    b: 'green',
    c: 'blue',
    d: 'gray'
};

const getColor = function (key) {
    if (key.includes(translations.revenue_account)) {
        return 'forestgreen';
    }
    if (key.includes('(' + translations.in + ',')) {
        return 'green';
    }

    if (key.includes(translations.budget) || key.includes(translations.unknown_budget)) {
        return 'Orchid';
    }
    if (key.includes('(' + translations.out + ',')) {
        return 'MediumOrchid';
    }

    if (key.includes(translations.all_money)) {
        return 'blue';
    }
    return 'red';
}

// little helper
function getObjectName(type, name, direction, code) {

    // category 4x
    if ('category' === type && null !== name && 'in' === direction) {
        return translations.category + ' "' + name + '" (' + translations.in + (autoConversion ? ', ' + code + ')' : ')');
    }
    if ('category' === type && null === name && 'in' === direction) {
        return translations.unknown_category + ' (' + translations.in + (autoConversion ? ', ' + code + ')' : ')');
    }
    if ('category' === type && null !== name && 'out' === direction) {
        return translations.category + ' "' + name + '" (' + translations.out + (autoConversion ? ', ' + code + ')' : ')');
    }
    if ('category' === type && null === name && 'out' === direction) {
        return translations.unknown_category + ' (' + translations.out + (autoConversion ? ', ' + code + ')' : ')');
    }
    // account 4x
    if ('account' === type && null === name && 'in' === direction) {
        return translations.unknown_source + (autoConversion ? ' (' + code + ')' : '');
    }
    if ('account' === type && null !== name && 'in' === direction) {
        return translations.revenue_account + '"' + name + '"' + (autoConversion ? ' (' + code + ')' : '');
    }
    if ('account' === type && null === name && 'out' === direction) {
        return translations.unknown_dest + (autoConversion ? ' (' + code + ')' : '');
    }
    if ('account' === type && null !== name && 'out' === direction) {
        return translations.expense_account + ' "' + name + '"' + (autoConversion ? ' (' + code + ')' : '');
    }

    // budget 2x
    if ('budget' === type && null !== name) {
        return translations.budget + ' "' + name + '"' + (autoConversion ? ' (' + code + ')' : '');
    }
    if ('budget' === type && null === name) {
        return translations.unknown_budget + (autoConversion ? ' (' + code + ')' : '');
    }
    console.error('Cannot handle: type:"' + type + '", dir: "' + direction + '"');
}

function getLabelName(type, name, code) {
    // category
    if ('category' === type && null !== name) {
        return translations.category + ' "' + name + '"' + (autoConversion ? ' (' + code + ')' : '');
    }
    if ('category' === type && null === name) {
        return translations.unknown_category + (autoConversion ? ' (' + code + ')' : '');
    }
    // account
    if ('account' === type && null === name) {
        return translations.unknown_account + (autoConversion ? ' (' + code + ')' : '');
    }
    if ('account' === type && null !== name) {
        return name + (autoConversion ? ' (' + code + ')' : '');
    }

    // budget 2x
    if ('budget' === type && null !== name) {
        return translations.budget + ' "' + name + '"' + (autoConversion ? ' (' + code + ')' : '');
    }
    if ('budget' === type && null === name) {
        return translations.unknown_budget + (autoConversion ? ' (' + code + ')' : '');
    }
    console.error('Cannot handle: type:"' + type + '"');
}


export default () => ({
    loading: false,
    autoConversion: false,
    generateOptions() {
        let options = getDefaultChartSettings('sankey');

        // reset currencies
        currencies = [];

        // variables collected for the sankey chart:
        let amounts = {};
        let labels = {};
        for (let i in transactions) {
            if (transactions.hasOwnProperty(i)) {
                let group = transactions[i];
                for (let ii in group.attributes.transactions) {
                    if (group.attributes.transactions.hasOwnProperty(ii)) {
                        // properties of the transaction, used in the generation of the chart:
                        let transaction = group.attributes.transactions[ii];
                        let currencyCode = this.autoConversion ? transaction.native_currency_code : transaction.currency_code;
                        let amount = this.autoConversion ? parseFloat(transaction.native_amount) : parseFloat(transaction.amount);
                        let flowKey;

                        /*
                        Two entries in the sankey diagram for deposits:
                        1. From the revenue account (source) to a category (in).
                        2. From the category (in) to the big inbox.
                         */
                        if ('deposit' === transaction.type) {
                            // nr 1
                            let category = getObjectName('category', transaction.category_name, 'in', currencyCode);
                            let revenueAccount = getObjectName('account', transaction.source_name, 'in', currencyCode);
                            labels[category] = getLabelName('category', transaction.category_name, currencyCode);
                            labels[revenueAccount] = getLabelName('account', transaction.source_name, currencyCode);
                            flowKey = revenueAccount + '-' + category + '-' + currencyCode;
                            if (!amounts.hasOwnProperty(flowKey)) {
                                amounts[flowKey] = {
                                    from: revenueAccount,
                                    to: category,
                                    amount: 0
                                };
                            }
                            amounts[flowKey].amount += amount;

                            // nr 2
                            flowKey = category + '-' + translations.all_money + '-' + currencyCode;
                            if (!amounts.hasOwnProperty(flowKey)) {
                                amounts[flowKey] = {
                                    from: category,
                                    to: translations.all_money + (this.autoConversion ? ' (' + currencyCode + ')' : ''),
                                    amount: 0
                                };
                            }
                            amounts[flowKey].amount += amount;
                        }
                        /*
                        Three entries in the sankey diagram for withdrawals:
                        1. From the big box to a budget.
                        2. From a budget to a category.
                        3. From a category to an expense account.
                         */
                        if ('withdrawal' === transaction.type) {
                            // 1.
                            let budget = getObjectName('budget', transaction.budget_name, 'out', currencyCode);
                            labels[budget] = getLabelName('budget', transaction.budget_name, currencyCode);
                            flowKey = translations.all_money + '-' + budget + '-' + currencyCode;

                            if (!amounts.hasOwnProperty(flowKey)) {
                                amounts[flowKey] = {
                                    from: translations.all_money + (this.autoConversion ? ' (' + currencyCode + ')' : ''),
                                    to: budget,
                                    amount: 0
                                };
                            }
                            amounts[flowKey].amount += amount;


                            // 2.
                            let category = getObjectName('category', transaction.category_name, 'out', currencyCode);
                            labels[category] = getLabelName('category', transaction.category_name, currencyCode);
                            flowKey = budget + '-' + category + '-' + currencyCode;

                            if (!amounts.hasOwnProperty(flowKey)) {
                                amounts[flowKey] = {
                                    from: budget,
                                    to: category,
                                    amount: 0
                                };
                            }
                            amounts[flowKey].amount += amount;

                            // 3.
                            let expenseAccount = getObjectName('account', transaction.destination_name, 'out', currencyCode);
                            labels[expenseAccount] = getLabelName('account', transaction.destination_name, currencyCode);
                            flowKey = category + '-' + expenseAccount + '-' + currencyCode;

                            if (!amounts.hasOwnProperty(flowKey)) {
                                amounts[flowKey] = {
                                    from: category,
                                    to: expenseAccount,
                                    amount: 0
                                };
                            }
                            amounts[flowKey].amount += amount;
                        }
                    }
                }
            }
        }

        let dataSet =
            // sankey chart has one data set.
            {
                label: 'Firefly III dashboard sankey chart',
                data: [],
                colorFrom: (c) => getColor(c.dataset.data[c.dataIndex] ? c.dataset.data[c.dataIndex].from : ''),
                colorTo: (c) => getColor(c.dataset.data[c.dataIndex] ? c.dataset.data[c.dataIndex].to : ''),
                colorMode: 'gradient', // or 'from' or 'to'
                labels: labels,
                size: 'min', // or 'min' if flow overlap is preferred
            };
        for (let i in amounts) {
            if (amounts.hasOwnProperty(i)) {
                let amount = amounts[i];
                dataSet.data.push({from: amount.from, to: amount.to, flow: amount.amount});
            }
        }
        options.data.datasets.push(dataSet);

        return options;
    },
    drawChart(options) {
        if (null !== chart) {
            chart.data.datasets = options.data.datasets;
            chart.update();
            return;
        }
        chart = new Chart(document.querySelector("#sankey-chart"), options);

    },
    getFreshData() {
        const start = new Date(window.store.get('start'));
        const end = new Date(window.store.get('end'));
        const cacheKey = getCacheKey(SANKEY_CACHE_KEY, {start: start, end: end});

        const cacheValid = window.store.get('cacheValid');
        let cachedData = window.store.get(cacheKey);

        if (cacheValid && typeof cachedData !== 'undefined') {
            transactions = cachedData;
            this.drawChart(this.generateOptions());
            this.loading = false;
            return;
        }


        let params = {
            start: format(start, 'y-MM-dd'),
            end: format(end, 'y-MM-dd'),
            type: 'withdrawal,deposit',
            page: 1
        };
        this.downloadTransactions(params);
    },
    downloadTransactions(params) {
        const start = new Date(window.store.get('start'));
        const end = new Date(window.store.get('end'));
        const cacheKey = getCacheKey(SANKEY_CACHE_KEY, {start: start, end: end});

        //console.log('Downloading page ' + params.page + '...');
        const getter = new Get();
        getter.list(params).then((response) => {
            transactions = [...transactions, ...response.data.data];
            //this.drawChart(this.generateOptions(response.data));
            //this.loading = false;
            if (parseInt(response.data.meta.pagination.total_pages) > params.page) {
                // continue to next page.
                params.page++;
                this.downloadTransactions(params);
                return;
            }
            window.store.set(cacheKey, transactions);
            this.drawChart(this.generateOptions());
            this.loading = false;
        });
    },

    loadChart() {
        if (true === this.loading) {
            return;
        }
        this.loading = true;

        if (0 !== transactions.length) {
            this.drawChart(this.generateOptions());
            this.loading = false;
            return;
        }
        this.getFreshData();
    },
    init() {
        // console.log('sankey init');
        transactions = [];
        Promise.all([getVariable('autoConversion', false)]).then((values) => {
            this.autoConversion = values[0];
            autoConversion = values[0];
                // some translations:
                translations.all_money = i18next.t('firefly.all_money');
                translations.category = i18next.t('firefly.category');
                translations.in = i18next.t('firefly.money_flowing_in');
                translations.out = i18next.t('firefly.money_flowing_out');
                translations.unknown_category = i18next.t('firefly.unknown_category_plain');
                translations.unknown_source = i18next.t('firefly.unknown_source_plain');
                translations.unknown_dest = i18next.t('firefly.unknown_dest_plain');
                translations.unknown_account = i18next.t('firefly.unknown_any_plain');
                translations.unknown_budget = i18next.t('firefly.unknown_budget_plain');
                translations.expense_account = i18next.t('firefly.expense_account');
                translations.revenue_account = i18next.t('firefly.revenue_account');
                translations.budget = i18next.t('firefly.budget');

                // console.log('sankey after promises');
                afterPromises = true;
                this.loadChart();

        });
        window.store.observe('end', () => {
            if (!afterPromises) {
                return;
            }
            // console.log('sankey observe end');
            this.transactions = [];
            this.loadChart();
        });
        window.store.observe('autoConversion', (newValue) => {
            if (!afterPromises) {
                return;
            }
            // console.log('sankey observe autoConversion');
            this.autoConversion = newValue;
            this.loadChart();
        });
    },

});


