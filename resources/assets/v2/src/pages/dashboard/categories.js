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
import Dashboard from "../../api/v1/chart/category/dashboard.js";
import {getDefaultChartSettings} from "../../support/default-chart-settings.js";
import {Chart} from "chart.js";
import formatMoney from "../../util/format-money.js";
import {getColors} from "../../support/get-colors.js";
import {getCacheKey} from "../../support/get-cache-key.js";

let currencies = [];
let chart = null;
let chartData = null;
let afterPromises = false;

export default () => ({
    loading: false,
    convertToPrimary: false,

    eventListeners: {
        ['@convert-to-primary.window'](event){
            console.log('I heard that! (dashboard/categories)');
            this.convertToPrimary = event.detail;
            chartData = null;
            this.loadChart();
        }
    },


    generateOptions(data) {
        currencies = [];
        let options = getDefaultChartSettings('column');

        // first, create "series" per currency.
        let series = {};
        for (const i in data) {
            if (data.hasOwnProperty(i)) {
                let current = data[i];
                let code = current.currency_code;
                if (!series.hasOwnProperty(code)) {
                    series[code] = {
                        name: code,
                        yAxisID: '',
                        data: {},
                    };
                    currencies.push(code);
                }
            }
        }

        // loop data again to add amounts to each series.
        for (const i in data) {
            if (data.hasOwnProperty(i)) {
                let yAxis = 'y';
                let current = data[i];
                let code = current.currency_code;

                // loop series, add 0 if not present or add actual amount.
                for (const ii in series) {
                    if (series.hasOwnProperty(ii)) {
                        let amount = 0.0;
                        if (code === ii) {
                            // this series' currency matches this column's currency.
                            amount = parseFloat(current.amount);
                            yAxis = 'y' + current.currency_code;
                        }
                        if (series[ii].data.hasOwnProperty(current.label)) {
                            // there is a value for this particular currency. The amount from this column will be added.
                            // (even if this column isn't recorded in this currency and a new filler value is written)
                            // this is so currency conversion works.
                            series[ii].data[current.label] = series[ii].data[current.label] + amount;
                        }

                        if (!series[ii].data.hasOwnProperty(current.label)) {
                            // this column's amount is not yet set in this series.
                            series[ii].data[current.label] = amount;
                        }
                    }
                }
                // add label to x-axis, not unimportant.
                if (!options.data.labels.includes(current.label)) {
                    options.data.labels.push(current.label);
                }
            }
        }
        // loop the series and create ChartJS-compatible data sets.
        let count = 0;
        for (const i in series) {
            // console.log('series');
            let yAxisID = 'y' + i;
            let dataset = {
                label: i,
                currency_code: i,
                yAxisID: yAxisID,
                data: [],
                // backgroundColor: getColors(null, 'background'),
                // borderColor: getColors(null, 'border'),
            }
            for (const ii in series[i].data) {
                dataset.data.push(series[i].data[ii]);
            }
            options.data.datasets.push(dataset);
            if (!options.options.scales.hasOwnProperty(yAxisID)) {
                options.options.scales[yAxisID] = {
                    beginAtZero: true,
                    type: 'linear',
                    position: 1 === count ? 'right' : 'left',
                    ticks: {
                        callback: function (value, index, values) {
                            return formatMoney(value, i);
                        }
                    }
                };
                count++;
            }
        }
        return options;
    },
    drawChart(options) {
        if (null !== chart) {
            chart.options = options.options;
            chart.data = options.data;
            chart.update();
            return;
        }
        chart = new Chart(document.querySelector("#category-chart"), options);

    },
    getFreshData() {
        const start = new Date(window.store.get('start'));
        const end = new Date(window.store.get('end'));
        const cacheKey = getCacheKey('ds_ct_chart', {convertToPrimary: this.convertToPrimary, start: start, end: end});

        const cacheValid = window.store.get('cacheValid');
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
            this.drawChart(this.generateOptions(response.data));
            window.store.set(cacheKey, chartData);
            this.loading = false;
        });
    },

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
    init() {
        // console.log('categories init');
        Promise.all([getVariable('convert_to_primary', false),]).then((values) => {
            this.convertToPrimary = values[0];
            afterPromises = true;
            this.loadChart();
        });
        window.store.observe('end', () => {
            if (!afterPromises) {
                return;
            }
            this.chartData = null;
            this.loadChart();
        });
        window.store.observe('convert_to_primary', (newValue) => {
            if (!afterPromises) {
                return;
            }
            this.convertToPrimary = newValue;
            this.loadChart();
        });
    },

});


