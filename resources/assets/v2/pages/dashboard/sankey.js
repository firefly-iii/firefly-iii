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
import Get from "../../api/v2/model/transaction/get.js";
import {getDefaultChartSettings} from "../../support/default-chart-settings.js";
import Chart from "chart.js/auto";
import {Flow, SankeyController} from 'chartjs-chart-sankey';

Chart.register(SankeyController, Flow);

let currencies = [];

let chart        = null;
let transactions = [];

// little helper
function getObjectName(type, name, direction, code) {
    // category 4x
    if ('category' === type && null !== name && 'in' === direction) {
        return 'Category "' + name + '" (in ' + code + ')';
    }
    if ('category' === type && null === name && 'in' === direction) {
        return 'Unknown category (in ' + code + ')';
    }
    if ('category' === type && null !== name && 'out' === direction) {
        return 'Category "' + name + '" (out ' + code + ')';
    }
    if ('category' === type && null === name && 'out' === direction) {
        return 'Unknown category (out ' + code + ')';
    }
    // account 4x
    if ('account' === type && null === name && 'in' === direction) {
        return 'Unknown source account ' + code + '';
    }
    if ('account' === type && null !== name && 'in' === direction) {
        return name + ' (in ' + code + ')';
    }
    if ('account' === type && null === name && 'out' === direction) {
        return 'Unknown destination account ' + code + '';
    }
    if ('account' === type && null !== name && 'out' === direction) {
        return name + ' (out ' + code + ')';
    }

    // budget 2x
    if ('budget' === type && null !== name && 'out' === direction) {
        return 'Budget "' + name + '" (out ' + code + ')';
    }
    if ('budget' === type && null === name && 'out' === direction) {
        return 'Unknown budget (' + code + ')';
    }
    console.error('Cannot handle: type:"' + type + '", dir: "' + direction + '"');
}

function getLabelName(type, name, code) {
    // category
    if ('category' === type && null !== name) {
        return 'Category "' + name + '" (' + code + ')';
    }
    if ('category' === type && null === name) {
        return 'Unknown category (' + code + ')';
    }
    // account
    if ('account' === type && null === name) {
        return 'Unknown account (' + code + ')';
    }
    if ('account' === type && null !== name) {
        return name + ' (' + code + ')';
    }

    // budget 2x
    if ('budget' === type && null !== name) {
        return 'Budget "' + name + '" (' + code + ')';
    }
    if ('budget' === type && null === name) {
        return 'Unknown budget (' + code + ')';
    }
    console.error('Cannot handle: type:"' + type + '"');
}

export default () => ({
    loading: false,
    autoConversion: false,
    sankeyGrouping: 'account',
    generateOptions(data) {
        let options = getDefaultChartSettings('sankey');

        // reset currencies
        currencies = [];

        // variables collected for the sankey chart:
        let amounts = {};
        let bigBox  = 'TODO All money';
        let labels  = {};
        for (let i in transactions) {
            if (transactions.hasOwnProperty(i)) {
                let group = transactions[i];
                for (let ii in group.attributes.transactions) {
                    if (group.attributes.transactions.hasOwnProperty(ii)) {
                        // properties of the transaction, used in the generation of the chart:
                        let transaction  = group.attributes.transactions[ii];
                        let currencyCode = this.autoConversion ? transaction.native_code : transaction.currency_code;
                        let amount       = this.autoConversion ? parseFloat(transaction.native_amount) : parseFloat(transaction.amount);
                        let flowKey;

                        /*
                        Two entries in the sankey diagram for deposits:
                        1. From the revenue account (source) to a category (in).
                        2. From the category (in) to the big inbox.
                         */
                        if ('deposit' === transaction.type) {
                            // nr 1
                            let category           = getObjectName('category', transaction.category_name, 'in', currencyCode);
                            let revenueAccount     = getObjectName('account', transaction.source_name, 'in', currencyCode);
                            labels[category]       = getLabelName('category', transaction.category_name, currencyCode);
                            labels[revenueAccount] = getLabelName('account', transaction.source_name, currencyCode);
                            flowKey                = revenueAccount + '-' + category + '-' + currencyCode;
                            if (!amounts.hasOwnProperty(flowKey)) {
                                amounts[flowKey] = {
                                    from: revenueAccount,
                                    to: category,
                                    amount: 0
                                };
                            }
                            amounts[flowKey].amount += amount;

                            // nr 2
                            flowKey = category + '-' + bigBox + '-' + currencyCode;
                            if (!amounts.hasOwnProperty(flowKey)) {
                                amounts[flowKey] = {
                                    from: category,
                                    to: bigBox,
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
                            let budget     = getObjectName('budget', transaction.budget_name, 'out', currencyCode);
                            labels[budget] = getLabelName('budget', transaction.budget_name, currencyCode);
                            flowKey        = bigBox + '-' + budget + '-' + currencyCode;

                            if (!amounts.hasOwnProperty(flowKey)) {
                                amounts[flowKey] = {
                                    from: bigBox,
                                    to: budget,
                                    amount: 0
                                };
                            }
                            amounts[flowKey].amount += amount;


                            // 2.
                            let category     = getObjectName('category', transaction.category_name, 'out', currencyCode);
                            labels[category] = getLabelName('category', transaction.category_name, currencyCode);
                            flowKey          = budget + '-' + category + '-' + currencyCode;

                            if (!amounts.hasOwnProperty(flowKey)) {
                                amounts[flowKey] = {
                                    from: budget,
                                    to: category,
                                    amount: 0
                                };
                            }
                            amounts[flowKey].amount += amount;

                            // 3.
                            let expenseAccount     = getObjectName('account', transaction.destination_name, 'out', currencyCode);
                            labels[expenseAccount] = getLabelName('account', transaction.destination_name, currencyCode);
                            flowKey                = category + '-' + expenseAccount + '-' + currencyCode;

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
                    label: 'My sankey',
                    data: [],
                    //colorFrom: (c) => getColor(c.dataset.data[c.dataIndex].from),
                    //colorTo: (c) => getColor(c.dataset.data[c.dataIndex].to),
                    colorMode: 'gradient', // or 'from' or 'to'
                    labels: labels,
                    /* optional labels */
                    // labels: {
                    //     a: 'Label A',
                    //     b: 'Label B',
                    //     c: 'Label C',
                    //     d: 'Label D'
                    // },
                    /* optional priority */
                    // priority: {
                    //     b: 1,
                    //     d: 0
                    // },
                    /* optional column overrides */
                    // column: {
                    //     d: 1
                    // },
                    size: 'max', // or 'min' if flow overlap is preferred
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
        let params = {
            start: window.store.get('start').slice(0, 10),
            end: window.store.get('end').slice(0, 10),
            type: 'withdrawal,deposit',
            page: 1
        };
        this.downloadTransactions(params);
    },
    downloadTransactions(params) {
        //console.log('Downloading page ' + params.page + '...');
        const getter = new Get();
        getter.get(params).then((response) => {
            transactions = [...transactions, ...response.data.data];
            //this.drawChart(this.generateOptions(response.data));
            //this.loading = false;
            if (parseInt(response.data.meta.pagination.total_pages) > params.page) {
                // continue to next page.
                params.page++;
                this.downloadTransactions(params);
                return;
            }
            // continue to next step.
            //console.log('Final page!');
            //console.log(transactions);
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
        transactions = [];
        Promise.all([getVariable('autoConversion', false), getVariable('sankeyGrouping', 'account')]).then((values) => {
            this.autoConversion = values[0];
            this.sankeyGrouping = values[1];
            this.loadChart();
        });
        window.store.observe('end', () => {
            this.transactions = [];
            this.loadChart();
        });
        window.store.observe('autoConversion', (newValue) => {
            this.autoConversion = newValue;
            this.loadChart();
        });
        window.store.observe('sankeyGrouping', (newValue) => {
            this.sankeyGrouping = newValue;
            this.loadChart();
        });
    },

});


