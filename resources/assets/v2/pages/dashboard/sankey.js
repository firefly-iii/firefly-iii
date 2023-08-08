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

let chart = null;
let transactions = [];

export default () => ({
    loading: false,
    autoConversion: false,
    generateOptions(data) {
        currencies = [];
        console.log('generate options');
        let options = getDefaultChartSettings('sankey');

        // temp code for first sankey
        const colors = {
            a: 'red',
            b: 'green',
            c: 'blue',
            d: 'gray'
        };

        const getColor = (key) => colors[key];
        // end of temp code for first sankey
        let dataSet =
            // sankey chart has one data set.
            {
                label: 'My sankey',
                data: [
                    {from: 'a', to: 'b', flow: 10},
                    {from: 'a', to: 'c', flow: 5},
                    {from: 'b', to: 'c', flow: 10},
                    {from: 'd', to: 'c', flow: 7}
                ],
                colorFrom: (c) => getColor(c.dataset.data[c.dataIndex].from),
                colorTo: (c) => getColor(c.dataset.data[c.dataIndex].to),
                colorMode: 'gradient', // or 'from' or 'to'
                /* optional labels */
                labels: {
                    a: 'Label A',
                    b: 'Label B',
                    c: 'Label C',
                    d: 'Label D'
                },
                /* optional priority */
                priority: {
                    b: 1,
                    d: 0
                },
                /* optional column overrides */
                column: {
                    d: 1
                },
                size: 'max', // or 'min' if flow overlap is preferred
            };
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
        console.log('Downloading page ' + params.page + '...');
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
            console.log('Final page!');
            console.log(transactions);
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
        Promise.all([getVariable('autoConversion', false),]).then((values) => {
            this.autoConversion = values[0];
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
    },

});


