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
let currencies         = [];
let afterPromises      = false;
let chart              = null;
let transactions       = [];
let convertToPrimary    = false;
let translations       = {
    category: null,
    unknown_category: null,
    in: null,
    out: null,
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
    if(convertToPrimary) {
        return getObjectNameWithoutCurrency(type, name, direction);
    }
    return getObjectNameWithCurrency(type, name, direction, code);

}

function getObjectNameWithoutCurrency(type, name, direction) {
    if('category' === type) {
        let catName = null === name ? translations.unknown_category : translations.category + ' "' + name + '"';
        let directionText = 'in' === direction ? translations.in : translations.out;
        return catName + ' (' + directionText + ')';
    }
    if('account' === type) {
        let accountName = null === name ? translations.unknown_account : name;
        let directionText = 'in' === direction ? translations.in : translations.out;
        let fullAccountName = 'in' === direction ? translations.revenue_account + ' "' + accountName + '"' : translations.expense_account + ' "' + accountName + '"';
        return fullAccountName + ' (' + directionText + ')';
    }
    if('budget' === type) {
        return null === name ? translations.unknown_budget : translations.budget + ' "' + name + '"';
    }
    console.error('[a] Cannot handle: type:"' + type + '", dir: "' + direction + '"');
}
function getObjectNameWithCurrency(type, name, direction, code) {
    if('category' === type) {
        let catName = null === name ? translations.unknown_category : translations.category + ' "' + name + '"';
        let directionText = 'in' === direction ? translations.in : translations.out;
        return catName + ' (' + directionText + ', ' + code + ')';
    }
    if('account' === type) {
        let accountName = null === name ? translations.unknown_account : name;
        let directionText = 'in' === direction ? translations.in : translations.out;
        let fullAccountName = 'in' === direction ? translations.revenue_account + ' "' + accountName + '"' : translations.expense_account + ' "' + accountName + '"';
        return fullAccountName + ' (' + directionText + ', ' + code + ')';
    }
    if('budget' === type) {
        return (null === name ? translations.unknown_budget : translations.budget + ' "' + name + '"') + ' (' + code + ')';
    }
    console.error('[b] Cannot handle: type:"' + type + '", dir: "' + direction + '"');
}


function getLabel(type, name, code) {
    if(convertToPrimary) {
        return getLabelWithoutCurrency(type, name);
    }
    return getLabelWithCurrency(type, name, code);

}

function getLabelWithoutCurrency(type, name) {
    if('category' === type) {
        return null === name ? translations.unknown_category : translations.category + ' "' + name + '"';
    }
    if('account' === type) {
        return null === name ? translations.unknown_account : name;
    }
    if('budget' === type) {
        return null === name ? translations.unknown_budget : translations.budget + ' "' + name + '"';
    }
    console.error('[a] Cannot handle: type:"' + type + '"');
}
function getLabelWithCurrency(type, name, code) {
    if('category' === type) {
        return (null === name ? translations.unknown_category : translations.category + ' "' + name + '"') + ' ('+ code + ')';
    }
    if('account' === type) {
        return (null === name ? translations.unknown_account : name)  + ' (' + code + ')';
    }
    if('budget' === type) {
        return (null === name ? translations.unknown_budget : translations.budget + ' "' + name + '"')  + ' (' + code + ')';;
    }
    console.error('[b] Cannot handle: type:"' + type + '"');
}

export default () => ({
    loading: false,
    convertToPrimary: false,
    processedData: null,
    eventListeners: {
        ['@convert-to-primary.window'](event){
            console.log('I heard that! (dashboard/sankey)');
            this.convertToPrimary = event.detail;
            convertToPrimary = event.detail;
            this.processedData = null;
            this.loadChart();
        }
    },


    generateOptions() {
        let options = getDefaultChartSettings('sankey');

        // reset currencies
        currencies = [];

        // variables collected for the sankey chart:
        this.parseTransactionGroups(transactions);

        let dataSet =
                // sankey chart has one data set.
                {
                    label: 'Firefly III dashboard sankey chart',
                    data: [],
                    colorFrom: (c) => getColor(c.dataset.data[c.dataIndex] ? c.dataset.data[c.dataIndex].from : ''),
                    colorTo: (c) => getColor(c.dataset.data[c.dataIndex] ? c.dataset.data[c.dataIndex].to : ''),
                    colorMode: 'gradient', // or 'from' or 'to'
                    labels: this.processedData.labels,
                    size: 'min', // or 'min' if flow overlap is preferred
                };
        for (let i in this.processedData.amounts) {
            if (this.processedData.amounts.hasOwnProperty(i)) {
                let amount = this.processedData.amounts[i];
                dataSet.data.push({from: amount.from, to: amount.to, flow: amount.amount});
            }
        }
        options.data.datasets.push(dataSet);

        return options;
    },
    parseTransactionGroups(groups) {
        this.processedData = {
            amounts: {},
            labels: {}
        };
        for (let i in groups) {
            if (groups.hasOwnProperty(i)) {
                let group = groups[i];
                this.parseTransactionGroup(group);
            }
        }
    },
    parseTransactionGroup(group) {
        for (let ii in group.attributes.transactions) {
            if (group.attributes.transactions.hasOwnProperty(ii)) {
                // properties of the transaction, used in the generation of the chart:
                let transaction = group.attributes.transactions[ii];
                this.parseTransaction(transaction);
            }
        }

    },
    parseTransaction(transaction) {
        let currencyCode = transaction.currency_code;
        let amount       = parseFloat(transaction.amount);
        let flowKey;
        if (this.convertToPrimary) {
            currencyCode = transaction.primary_currency_code;
            amount       = parseFloat(transaction.pc_amount);
        }
        if ('deposit' === transaction.type) {
            this.parseDeposit(transaction, currencyCode, amount);
            return;
        }

        if ('withdrawal' === transaction.type) {
            this.parseWithdrawal(transaction, currencyCode, amount);
        }
    },
    parseWithdrawal(transaction, currencyCode, amount) {
        /*
        Three entries in the sankey diagram for withdrawals:
        1. From the big box to a budget.
        2. From a budget to a category.
        3. From a category to an expense account.
        */

        // first one:
        let budget                        = getObjectName('budget', transaction.budget_name, 'out', currencyCode);
        this.processedData.labels[budget] = getLabel('budget', transaction.budget_name, currencyCode);
        let flowKey                       = translations.all_money + '-' + budget + '-' + currencyCode;

        if (!this.processedData.amounts.hasOwnProperty(flowKey)) {
            this.processedData.amounts[flowKey] = {
                from: translations.all_money + (this.convertToPrimary ? ' (' + currencyCode + ')' : ''),
                to: budget,
                amount: 0
            };
        }
        this.processedData.amounts[flowKey].amount += amount;


        // second one:
        let category                        = getObjectName('category', transaction.category_name, 'out', currencyCode);
        this.processedData.labels[category] = getLabel('category', transaction.category_name, currencyCode);
        flowKey                             = budget + '-' + category + '-' + currencyCode;

        if (!this.processedData.amounts.hasOwnProperty(flowKey)) {
            this.processedData.amounts[flowKey] = {
                from: budget,
                to: category,
                amount: 0
            };
        }
        this.processedData.amounts[flowKey].amount += amount;

        // third one:
        let expenseAccount                        = getObjectName('account', transaction.destination_name, 'out', currencyCode);
        this.processedData.labels[expenseAccount] = getLabel('account', transaction.destination_name, currencyCode);
        flowKey                                   = category + '-' + expenseAccount + '-' + currencyCode;

        if (!this.processedData.amounts.hasOwnProperty(flowKey)) {
            this.processedData.amounts[flowKey] = {
                from: category,
                to: expenseAccount,
                amount: 0
            };
        }
        this.processedData.amounts[flowKey].amount += amount;
    },
    parseDeposit(transaction, currencyCode, amount) {
        /*
        Two entries in the sankey diagram for deposits:
        1. From the revenue account (source) to a category (in).
        2. From the category (in) to the big inbox.
        */

        // this is the first one:
        let category                              = getObjectName('category', transaction.category_name, 'in', currencyCode);
        let revenueAccount                        = getObjectName('account', transaction.source_name, 'in', currencyCode);
        let flowKey                               = revenueAccount + '-' + category + '-' + currencyCode;
        this.processedData.labels[category]       = getLabel('category', transaction.category_name, currencyCode);
        this.processedData.labels[revenueAccount] = getLabel('account', transaction.source_name, currencyCode);

        // create if necessary:
        if (!this.processedData.amounts.hasOwnProperty(flowKey)) {
            this.processedData.amounts[flowKey] = {
                from: revenueAccount,
                to: category,
                amount: 0
            };
        }
        this.processedData.amounts[flowKey].amount += amount;

        // this is the second one:
        flowKey = category + '-' + translations.all_money + '-' + currencyCode;
        if (!this.processedData.amounts.hasOwnProperty(flowKey)) {
            this.processedData.amounts[flowKey] = {
                from: category,
                to: translations.all_money + (this.convertToPrimary ? ' (' + currencyCode + ')' : ''),
                amount: 0
            };
        }
        this.processedData.amounts[flowKey].amount += amount;
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
        const start    = new Date(window.store.get('start'));
        const end      = new Date(window.store.get('end'));
        const cacheKey = getCacheKey(SANKEY_CACHE_KEY, {start: start, end: end});

        const cacheValid = window.store.get('cacheValid');
        let cachedData   = window.store.get(cacheKey);

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
        const start    = new Date(window.store.get('start'));
        const end      = new Date(window.store.get('end'));
        const cacheKey = getCacheKey(SANKEY_CACHE_KEY, {convertToPrimary: this.convertToPrimary, start: start, end: end});

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
        Promise.all([getVariable('convert_to_primary', false)]).then((values) => {
            this.convertToPrimary = values[0];
            convertToPrimary      = values[0];

            // some translations:
            translations.all_money        = i18next.t('firefly.all_money');
            translations.category         = i18next.t('firefly.category');
            translations.in               = i18next.t('firefly.money_flowing_in');
            translations.out              = i18next.t('firefly.money_flowing_out');
            translations.unknown_category = i18next.t('firefly.unknown_category_plain');
            translations.unknown_source   = i18next.t('firefly.unknown_source_plain');
            translations.unknown_dest     = i18next.t('firefly.unknown_dest_plain');
            translations.unknown_account  = i18next.t('firefly.unknown_any_plain');
            translations.unknown_budget   = i18next.t('firefly.unknown_budget_plain');
            translations.expense_account  = i18next.t('firefly.expense_account');
            translations.revenue_account  = i18next.t('firefly.revenue_account');
            translations.budget           = i18next.t('firefly.budget');

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
        window.store.observe('convert_to_primary', (newValue) => {
            if (!afterPromises) {
                return;
            }
            // console.log('sankey observe convertToPrimary');
            this.convertToPrimary = newValue;
            this.loadChart();
        });
    },

});


