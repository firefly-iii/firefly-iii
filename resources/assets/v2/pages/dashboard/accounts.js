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

import ApexCharts from "apexcharts";
import {getVariable} from "../../store/get-variable.js";
import {setVariable} from "../../store/set-variable.js";
import Dashboard from "../../api/v2/chart/account/dashboard.js";
import formatLocal from "../../util/format.js";
import {format} from "date-fns";
import formatMoney from "../../util/format-money.js";
import Get from "../../api/v1/accounts/get.js";

// this is very ugly, but I have no better ideas at the moment to save the currency info
// for each series.
window.currencies = [];

export default () => ({
    loading: false,
    loadingAccounts: false,
    accountList: [],
    autoConversion: false,
    chart: null,
    switchAutoConversion() {
        this.autoConversion = !this.autoConversion;
        setVariable('autoConversion', this.autoConversion);
        this.loadChart();
    },
    loadChart() {
        if (this.loading) {
            return;
        }
        // load chart data
        this.loading = true;
        const dashboard = new Dashboard();
        dashboard.dashboard(new Date(window.store.get('start')), new Date(window.store.get('end')), null).then((response) => {

            // chart options (may need to be centralized later on)
            window.currencies = [];
            let options = {
                legend: {show: false},
                chart: {
                    height: 400,
                    toolbar: {tools: {zoom: false, download: false, pan: false}}, type: 'line'
                }, series: [],
                settings: [],
                xaxis: {
                    categories: [], labels: {
                        formatter: function (value) {
                            if (undefined === value) {
                                return '';
                            }
                            const date = new Date(value);
                            if (date instanceof Date && !isNaN(date)) {
                                return formatLocal(date, 'PP');
                            }
                            console.error('Could not parse "' + value + '", return "".');
                            return ':(';
                        }
                    }
                }, yaxis: {
                    labels: {
                        formatter: function (value, index) {
                            if (undefined === value) {
                                return value;
                            }
                            if (undefined === index) {
                                return value;
                            }
                            if (typeof index === 'object') {
                                index = index.seriesIndex;
                            }
                            //console.log(index);
                            let currencyCode = window.currencies[index] ?? 'EUR';
                            return formatMoney(value, currencyCode);
                        }
                    }
                },
            };
            // render data:
            for (let i = 0; i < response.data.length; i++) {
                if (response.data.hasOwnProperty(i)) {
                    let current = response.data[i];
                    let entry = [];
                    let collection = [];
                    // use the "native" currency code and use the "native_entries" as array
                    if (this.autoConversion) {
                        window.currencies.push(current.native_code);
                        collection = current.native_entries;
                    }
                    if (!this.autoConversion) {
                        window.currencies.push(current.currency_code);
                        collection = current.entries;
                    }

                    for (const [ii, value] of Object.entries(collection)) {
                        entry.push({x: format(new Date(ii), 'yyyy-MM-dd'), y: parseFloat(value)});
                    }
                    options.series.push({name: current.label, data: entry});
                }
            }
            if (null !== this.chart) {
                // chart already in place, refresh:
                this.chart.updateOptions(options);
            }
            if (null === this.chart) {
                this.chart = new ApexCharts(document.querySelector("#account-chart"), options);
                this.chart.render();
            }
            this.loading = false;
        });
    }, loadAccounts() {
        if (this.loadingAccounts) {
            return;
        }
        this.loadingAccounts = true;
        const max = 10;
        Promise.all([getVariable('frontpageAccounts'),]).then((values) => {
            for (let i = 0; i < values[0].length; i++) {
                if (values[0].hasOwnProperty(i)) {
                    let accountId = values[0][i];
                    // grab account info for box:
                    (new Get).get(accountId, new Date(window.store.get('end'))).then((response) => {
                        let current = response.data.data;
                        this.accountList[i] = {
                            name: current.attributes.name,
                            id: current.id,
                            balance: formatMoney(current.attributes.current_balance, current.attributes.currency_code),
                            groups: [],
                        };

                        // get groups for account:
                        (new Get).transactions(current.id, 1).then((response) => {
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
                                    group.transactions.push({
                                        description: currentTransaction.description,
                                        id: current.id,
                                        amount: formatMoney(currentTransaction.amount, currentTransaction.currency_code),
                                    });
                                }
                                this.accountList[i].groups.push(group);
                            }
                            // will become false after the FIRST account is loaded.
                            this.loadingAccounts = false;
                        });
                    }).then(() => {
                        // console.log(this.accountList);
                    });
                }
            }

        });
    },

    init() {
        // console.log('init');
        Promise.all([getVariable('viewRange', '1M'), getVariable('autoConversion', false),]).then((values) => {
            this.autoConversion = values[1];
            // console.log(values[1]);
            this.loadChart();
            this.loadAccounts();
        });
        window.store.observe('end', () => {
            this.loadChart();
            this.loadAccounts();
        });
    },
});
