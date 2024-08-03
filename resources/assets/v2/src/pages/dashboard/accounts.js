/*
 * accounts.js
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
import {setVariable} from "../../store/set-variable.js";
import Dashboard from "../../api/v2/chart/account/dashboard.js";
import formatMoney from "../../util/format-money.js";
import Get from "../../api/v2/model/account/get.js";
import {Chart} from 'chart.js';
import {getDefaultChartSettings} from "../../support/default-chart-settings.js";
import {getCacheKey} from "../../support/get-cache-key.js";
import {getConfiguration} from "../../store/get-configuration.js";

// this is very ugly, but I have no better ideas at the moment to save the currency info
// for each series.
let currencies = [];
let chart = null;
let chartData = null;
let afterPromises = false;

export default () => ({
    loading: false,
    loadingAccounts: false,
    accountList: [],
    autoConversion: false,
    autoConversionAvailable: false,
    chartOptions: null,
    switchAutoConversion() {
        this.autoConversion = !this.autoConversion;
        setVariable('autoConversion', this.autoConversion);
    },
    localCacheKey(type) {
        return 'ds_accounts_' + type;
    },
    getFreshData() {
        const start = new Date(window.store.get('start'));
        const end = new Date(window.store.get('end'));
        const chartCacheKey = getCacheKey(this.localCacheKey('chart'), {start: start, end: end})

        const cacheValid = window.store.get('cacheValid');
        let cachedData = window.store.get(chartCacheKey);

        if (cacheValid && typeof cachedData !== 'undefined') {
            this.drawChart(this.generateOptions(cachedData));
            this.loading = false;
            return;
        }
        const dashboard = new Dashboard();
        dashboard.dashboard(start, end, null).then((response) => {
            this.chartData = response.data;
            // cache generated options:
            window.store.set(chartCacheKey, response.data);
            this.drawChart(this.generateOptions(this.chartData));
            this.loading = false;
        });

    },
    generateOptions(data) {
        currencies = [];
        let options = getDefaultChartSettings('line');

        for (let i = 0; i < data.length; i++) {
            if (data.hasOwnProperty(i)) {
                let yAxis = 'y';
                let current = data[i];
                let dataset = {};
                let collection = [];

                // if index = 0, push all keys as labels:
                if (0 === i) {
                    options.data.labels = Object.keys(current.entries);
                }
                dataset.label = current.label;

                // use the "native" currency code and use the "native_entries" as array
                if (this.autoConversion) {
                    currencies.push(current.native_currency_code);
                    dataset.currency_code = current.native_currency_code;
                    collection = Object.values(current.native_entries);
                    yAxis = 'y' + current.native_currency_code;
                }
                if (!this.autoConversion) {
                    yAxis = 'y' + current.currency_code;
                    dataset.currency_code = current.currency_code;
                    currencies.push(current.currency_code);
                    collection = Object.values(current.entries);
                }
                dataset.yAxisID = yAxis;
                dataset.data = collection;

                // add colors:
                //dataset.backgroundColor = getColors(null, 'background');
                //dataset.borderColor = getColors(null, 'background');

                // add data set to the correct Y Axis:

                options.data.datasets.push(dataset);
            }
        }
        // for each entry in currencies, add a new y-axis:
        for (let currency in currencies) {
            if (currencies.hasOwnProperty(currency)) {
                let code = 'y' + currencies[currency];
                if (!options.options.scales.hasOwnProperty(code)) {
                    options.options.scales[code] = {
                        id: currency,
                        type: 'linear',
                        position: 1 === parseInt(currency) ? 'right' : 'left',
                        ticks: {
                            callback: function (value, index, values) {
                                return formatMoney(value, currencies[currency]);
                            }
                        }
                    };

                }
            }
        }
        return options;
    },
    loadChart() {
        if (true === this.loading) {
            return;
        }
        this.loading = true;
        if (null === chartData) {
            this.getFreshData();
            return;
        }
        this.drawChart(this.generateOptions(chartData));
        this.loading = false;

    },
    drawChart(options) {
        if (null !== chart) {
            // chart already in place, refresh:
            chart.options = options.options;
            chart.data = options.data;
            chart.update();
            return;
        }
        chart = new Chart(document.querySelector("#account-chart"), options);
    },
    loadAccounts() {
        if (true === this.loadingAccounts) {
            return;
        }
        this.loadingAccounts = true;
        if (this.accountList.length > 0) {
            this.loadingAccounts = false;
            return;
        }
        const start = new Date(window.store.get('start'));
        const end = new Date(window.store.get('end'));
        const accountCacheKey = getCacheKey(this.localCacheKey('data'), {start: start, end: end});

        const cacheValid = window.store.get('cacheValid');
        let cachedData = window.store.get(accountCacheKey);

        if (cacheValid && typeof cachedData !== 'undefined') {
            this.accountList = cachedData;
            this.loadingAccounts = false;
            return;
        }

        // console.log('loadAccounts continue!');
        const max = 10;
        let totalAccounts = 0;
        let count = 0;
        let accounts = [];
        Promise.all([getVariable('frontpageAccounts'),]).then((values) => {
            totalAccounts = values[0].length;
            //console.log(values[0]);
            for (let i in values[0]) {
                let account = values[0];
                if (account.hasOwnProperty(i)) {
                    let accountId = account[i];
                    // grab account info for box:
                    (new Get).show(accountId, new Date(window.store.get('end'))).then((response) => {
                        let parent = response.data.data;

                        // get groups for account:
                        const params = {
                            page: 1,
                            start: new Date(window.store.get('start')),
                            end: new Date(window.store.get('end')),
                        };
                        (new Get).transactions(parent.id, params).then((response) => {
                            let groups = [];
                            for (let ii = 0; ii < response.data.data.length; ii++) {
                                if (ii >= max) {
                                    break;
                                }
                                let current = response.data.data[ii];
                                let group = {
                                    title: null === current.attributes.group_title ? '' : current.attributes.group_title,
                                    id: current.id,
                                    transactions: [],
                                };
                                for (let iii = 0; iii < current.attributes.transactions.length; iii++) {
                                    let currentTransaction = current.attributes.transactions[iii];
                                    //console.log(currentTransaction);
                                    let nativeAmountRaw = 'withdrawal' === currentTransaction.type ? parseFloat(currentTransaction.native_amount) * -1 : parseFloat(currentTransaction.native_amount);
                                    let amountRaw = 'withdrawal' === currentTransaction.type ? parseFloat(currentTransaction.amount) * -1 : parseFloat(currentTransaction.amount);

                                    // if transfer and source is this account, multiply again
                                    if('transfer' === currentTransaction.type && parseInt(currentTransaction.source_id) === accountId) { //
                                        nativeAmountRaw = nativeAmountRaw * -1;
                                        amountRaw = amountRaw * -1;
                                    }

                                    group.transactions.push({
                                        description: currentTransaction.description,
                                        id: current.id,
                                        type: currentTransaction.type,
                                        amount_raw: amountRaw,
                                        amount: formatMoney(amountRaw, currentTransaction.currency_code),
                                        native_amount_raw: nativeAmountRaw,
                                        native_amount: formatMoney(nativeAmountRaw, currentTransaction.native_currency_code),
                                    });
                                }
                                groups.push(group);
                            }
                            // console.log(parent);
                            accounts.push({
                                name: parent.attributes.name,
                                order: parent.attributes.order,
                                id: parent.id,
                                balance: parent.attributes.balance,
                                native_balance: parent.attributes.native_balance,
                                groups: groups,
                            });
                            // console.log(parent.attributes);
                            count++;
                            if (count === totalAccounts) {
                                accounts.sort((a, b) => a.order - b.order); // b - a for reverse sort

                                this.accountList = accounts;
                                this.loadingAccounts = false;
                                window.store.set(accountCacheKey, accounts);
                            }
                        });
                    });
                }
            }
            //this.loadingAccounts = false;
        });
    },

    init() {
        // console.log('accounts init');
        Promise.all([getVariable('viewRange', '1M'), getVariable('autoConversion', false), getVariable('language', 'en_US'),
            getConfiguration('cer.enabled', false)
        ]).then((values) => {
            //console.log('accounts after promises');
            this.autoConversion = values[1] && values[3];
            this.autoConversionAvailable = values[3];
            afterPromises = true;

            // main dashboard chart:
            this.loadChart();
            this.loadAccounts();
        });
        window.store.observe('end', () => {
            if (!afterPromises) {
                return;
            }
            // console.log('accounts observe end');
            chartData = null;
            this.accountList = [];
            // main dashboard chart:
            this.loadChart();
            this.loadAccounts();
        });
        window.store.observe('autoConversion', () => {
            if (!afterPromises) {
                return;
            }
            // console.log('accounts observe autoconversion');
            this.loadChart();
            this.loadAccounts();
        });
    },
});
