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
import Dashboard from "../../api/v1/chart/budget/dashboard.js";
import {getDefaultChartSettings} from "../../support/default-chart-settings.js";
import formatMoney from "../../util/format-money.js";
import {Chart} from 'chart.js';
import {getColors} from "../../support/get-colors.js";
import {getCacheKey} from "../../support/get-cache-key.js";
import i18next from "i18next";

let currencies = [];
let chart = null;
let chartData = null;
let afterPromises = false;


export default () => ({
    loading: false,
    convertToPrimary: false,
    loadChart() {
        if (true === this.loading) {
            return;
        }
        this.loading = true;

        if (null !== chartData) {
            this.drawChart(this.generateOptions(chartData));
            this.loading = false;
            return;
        }
        this.getFreshData();
    },

    eventListeners: {
        ['@convert-to-primary.window'](event){
            console.log('I heard that! (dashboard/budgets)');
            this.convertToPrimary = event.detail;
            chartData = null;
            this.loadChart();
        }
    },

    drawChart(options) {
        if (null !== chart) {
            chart.data = options.data;
            chart.update();
            return;
        }
        chart = new Chart(document.querySelector("#budget-chart"), options);
    },
    getFreshData() {
        const start = new Date(window.store.get('start'));
        const end = new Date(window.store.get('end'));
        const cacheKey = getCacheKey('ds_bdg_chart', {convertToPrimary: this.convertToPrimary, start: start, end: end});
        //const cacheValid = window.store.get('cacheValid');
        const cacheValid = false;
        let cachedData = window.store.get(cacheKey);

        if (cacheValid && typeof cachedData !== 'undefined') {
            chartData = cachedData; // save chart data for later.
            this.drawChart(this.generateOptions(chartData));
            this.loading = false;
            return;
        }

        const dashboard = new Dashboard();
        dashboard.dashboard(start, end, null).then((response) => {
            chartData = response.data; // save chart data for later.
            this.drawChart(this.generateOptions(chartData));
            window.store.set(cacheKey, chartData);
            this.loading = false;
        });
    },
    generateOptions(data) {
        currencies = [];
        let options = getDefaultChartSettings('bar');
        options.options.locale = window.store.get('locale').replace('_', '-');
        options.options.plugins = {
            tooltip: {
                callbacks: {
                    title: function (context) {
                        return context.label;
                    },
                    label: function (context) {
                        let label = context.dataset.label || '';

                        if (label) {
                            label += ': ';
                        }
                        return label + ' ' + formatMoney(context.parsed.x, currencies[context.parsed.x] ?? 'EUR');
                    }
                }
            }
        };
        options.data = {
            labels: [],
            datasets: [
                {
                    label: i18next.t('firefly.budgeted'),
                    data: [],
                    borderWidth: 1,
                    backgroundColor: getColors('budgeted', 'background'),
                    borderColor: getColors('budgeted', 'border'),
                },
                {
                    //label: i18next.t('firefly.budgeted'),
                    label: i18next.t('firefly.spent'),
                    data: [],
                    borderWidth: 1,
                    backgroundColor: getColors('spent', 'background'),
                    borderColor: getColors('spent', 'border'),
                }
            ]
        };
        for (const i in data) {
            if (data.hasOwnProperty(i)) {
                let current = data[i];
                //         // convert to EUR yes no?
                let label = current.label + ' (' + current.currency_code + ')';
                options.data.labels.push(label);
                // label = current.label + ' (' + current.currency_code + ') b';
                // options.data.labels.push(label);

                currencies.push(current.currency_code);
                // series 0: budgeted
                options.data.datasets[0].data.push(parseFloat(current.entries.budgeted));
                // series 1: spent
                options.data.datasets[1].data.push(parseFloat(current.entries.spent) * -1);
                // series 2: overspent
                // options.data.datasets[2].data.push(parseFloat(current.entries.overspent));
            }
        }
        // the currency format callback for the Y axis is AlWAYS based on whatever the first currency is.

        // start
        options.options.scales = {
            y: {
                ticks: {
                    // callback: function (context) {
                    //     return 'abc';
                    //     return formatMoney(context, currencies[0] ?? 'EUR');
                    // }
                }
            }
        };
        // end
        return options;
    },


    init() {
        Promise.all([getVariable('convert_to_primary', false)]).then((values) => {
            this.convertToPrimary = values[0];
            afterPromises = true;
            if (false === this.loading) {
                this.loadChart();
            }
        });
        window.store.observe('end', () => {
            if (!afterPromises) {
                return;
            }
            // console.log('boxes observe end');
            if (false === this.loading) {
                chartData = null;
                this.loadChart();
            }
        });
        window.store.observe('convert_to_primary', (newValue) => {
            if (!afterPromises) {
                return;
            }
            // console.log('boxes observe convertToPrimary');
            this.convertToPrimary = newValue;
            if (false === this.loading) {
                this.loadChart();
            }
        });
    },

});


