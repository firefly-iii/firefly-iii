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
import Dashboard from "../../api/v2/chart/budget/dashboard.js";
import {getDefaultChartSettings} from "../../support/default-chart-settings.js";
import formatMoney from "../../util/format-money.js";
import {Chart} from 'chart.js';
import {I18n} from "i18n-js";
import {loadTranslations} from "../../support/load-translations.js";

let currencies = [];
let chart = null;
let chartData = null;
let afterPromises = false;

let i18n; // for translating items in the chart.


export default () => ({
    loading: false,
    autoConversion: false,
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
    drawChart(options) {
        if (null !== chart) {
            chart.data.datasets = options.data.datasets;
            chart.update();
            return;
        }
        chart = new Chart(document.querySelector("#budget-chart"), options);
    },
    getFreshData() {
        const dashboard = new Dashboard();
        dashboard.dashboard(new Date(window.store.get('start')), new Date(window.store.get('end')), null).then((response) => {
            chartData = response.data; // save chart data for later.
            this.drawChart(this.generateOptions(response.data));
            this.loading = false;
        });
    },
    generateOptions(data) {
        currencies = [];
        let options = getDefaultChartSettings('column');
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
                        return label + ' ' + formatMoney(context.parsed.y, currencies[context.parsed.x] ?? 'EUR');
                    }
                }
            }
        };
        options.data = {
            labels: [],
            datasets: [
                {
                    label: i18n.t('firefly.spent'),
                    data: [],
                    borderWidth: 1,
                    stack: 1
                },
                {
                    label: i18n.t('firefly.left'),
                    data: [],
                    borderWidth: 1,
                    stack: 1
                },
                {
                    label: i18n.t('firefly.overspent'),
                    data: [],
                    borderWidth: 1,
                    stack: 1
                }
            ]
        };
        for (const i in data) {
            if (data.hasOwnProperty(i)) {
                let current = data[i];
                //         // convert to EUR yes no?
                let label = current.label + ' (' + current.currency_code + ')';
                options.data.labels.push(label);
                if (this.autoConversion) {
                    currencies.push(current.native_code);
                    // series 0: spent
                    options.data.datasets[0].data.push(parseFloat(current.native_entries.spent) * -1);
                    // series 1: left
                    options.data.datasets[1].data.push(parseFloat(current.native_entries.left));
                    // series 2: overspent
                    options.data.datasets[2].data.push(parseFloat(current.native_entries.overspent));
                }
                if (!this.autoConversion) {
                    currencies.push(current.currency_code);
                    // series 0: spent
                    options.data.datasets[0].data.push(parseFloat(current.entries.spent) * -1);
                    // series 1: left
                    options.data.datasets[1].data.push(parseFloat(current.entries.left));
                    // series 2: overspent
                    options.data.datasets[2].data.push(parseFloat(current.entries.overspent));
                }
            }
        }
        // the currency format callback for the Y axis is AlWAYS based on whatever the first currency is.

        // start
        options.options.scales = {
            y: {
                ticks: {
                    callback: function (context) {
                        return formatMoney(context, currencies[0]);
                    }
                }
            }
        };
        // end
        return options;
    },


    init() {
        // console.log('budgets init');
        Promise.all([getVariable('autoConversion', false), getVariable('language', 'en-US')]).then((values) => {

            i18n = new I18n();
            i18n.locale = values[1];
            loadTranslations(i18n, values[1]);

            this.autoConversion = values[0];
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
                this.chartData = null;
                this.loadChart();
            }
        });
        window.store.observe('autoConversion', (newValue) => {
            if (!afterPromises) {
                return;
            }
            // console.log('boxes observe autoConversion');
            this.autoConversion = newValue;
            if (false === this.loading) {
                this.loadChart();
            }
        });
    },

});


