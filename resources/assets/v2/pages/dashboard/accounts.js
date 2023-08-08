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

//import ApexCharts from "apexcharts";
import {getVariable} from "../../store/get-variable.js";
import {setVariable} from "../../store/set-variable.js";
import Dashboard from "../../api/v2/chart/account/dashboard.js";
import formatMoney from "../../util/format-money.js";
import Get from "../../api/v1/accounts/get.js";
import Chart from "chart.js/auto";

// this is very ugly, but I have no better ideas at the moment to save the currency info
// for each series.
let currencies = [];
let chart = null;
let chartData = null;

export default () => ({
    loading: false,
    loadingAccounts: false,
    accountList: [],
    autoConversion: false,
    chartOptions: null,
    switchAutoConversion() {
        this.autoConversion = !this.autoConversion;
        setVariable('autoConversion', this.autoConversion);
    },
    getFreshData() {
        const dashboard = new Dashboard();
        dashboard.dashboard(new Date(window.store.get('start')), new Date(window.store.get('end')), null).then((response) => {
            this.chartData = response.data;
            this.drawChart(this.generateOptions(this.chartData));
        });
    },
    generateOptions(data) {
        currencies = [];
        let options = {
            type: 'line',
            data: {
                labels: [],
                datasets: []
            },
        };

        for (let i = 0; i < data.length; i++) {
            if (data.hasOwnProperty(i)) {
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
                    currencies.push(current.native_code);
                    collection = Object.values(current.native_entries);
                }
                if (!this.autoConversion) {
                    currencies.push(current.currency_code);
                    collection = Object.values(current.entries);
                }
                dataset.data = collection;

                for (const [ii, value] of Object.entries(collection)) {
                    //entry.push({x: format(new Date(ii), 'yyyy-MM-dd'), y: parseFloat(value)});
                }
                options.data.datasets.push(dataset);
                //options.series.push({name: current.label, data: entry});
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
            chart.data.datasets = options.data.datasets;
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
        const max = 10;
        let totalAccounts = 0;
        let count = 0;
        let accounts = [];
        Promise.all([getVariable('frontpageAccounts'),]).then((values) => {
            totalAccounts = values[0].length;
            for (let i in values[0]) {
                let account = values[0];
                if (account.hasOwnProperty(i)) {
                    let accountId = account[i];
                    // grab account info for box:
                    (new Get).get(accountId, new Date(window.store.get('end'))).then((response) => {
                        let parent = response.data.data;

                        // get groups for account:
                        (new Get).transactions(parent.id, 1).then((response) => {
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
                                    group.transactions.push({
                                        description: currentTransaction.description,
                                        id: current.id,
                                        amount: formatMoney(currentTransaction.amount, currentTransaction.currency_code),
                                    });
                                }
                                groups.push(group);
                            }
                            accounts.push({
                                name: parent.attributes.name,
                                id: parent.id,
                                balance: formatMoney(parent.attributes.current_balance, parent.attributes.currency_code),
                                groups: groups,
                            });
                            count++;
                            if (count === totalAccounts) {
                                this.accountList = accounts;
                            }
                        });
                    });
                }
            }
            //this.loadingAccounts = false;
        });
    },

    init() {
        Promise.all([getVariable('viewRange', '1M'), getVariable('autoConversion', false),]).then((values) => {
            this.autoConversion = values[1];
            // main dashboard chart:
            this.loadChart();
            this.loadAccounts();
        });
        window.store.observe('end', () => {
            chartData = null;
            // main dashboard chart:
            this.loadChart();
            this.loadAccounts();
        });
    },
});
