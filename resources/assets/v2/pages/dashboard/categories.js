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
import Dashboard from "../../api/v2/chart/category/dashboard.js";
//import ApexCharts from "apexcharts";
import formatMoney from "../../util/format-money.js";

window.categoryCurrencies = [];
export default () => ({
    loading: false,
    chart: null,
    autoConversion: false,
    chartData: null,
    chartOptions: null,
    generateOptions(data) {
        window.categoryCurrencies = [];
        let options = {
            series: [],
            chart: {
                type: 'bar',
                height: 350
            },
            plotOptions: {
                bar: {
                    horizontal: false,
                    columnWidth: '55%',
                    endingShape: 'rounded'
                },
            },
            dataLabels: {
                enabled: false
            },
            stroke: {
                show: true,
                width: 2,
                colors: ['transparent']
            },
            xaxis: {
                categories: [],
            },
            yaxis: {
                labels: {
                    formatter: function (value, index) {
                        if (undefined === value) {
                            return value;
                        }
                        if (undefined === index) {
                            return value;
                        }
                        if (typeof index === 'object') {
                            index = index.dataPointIndex; // this is the "category name + currency" index
                        }
                        let currencyCode = window.budgetCurrencies[index] ?? 'EUR';
                        return formatMoney(value, currencyCode);
                    }
                }
            },
            fill: {
                opacity: 1
            },
            tooltip: {
                y: {
                    formatter: function (value, index) {
                        if (undefined === value) {
                            return value;
                        }
                        if (undefined === index) {
                            return value;
                        }
                        if (typeof index === 'object') {
                            index = index.seriesIndex; // this is the currency index.
                        }
                        let currencyCode = window.categoryCurrencies[index] ?? 'EUR';
                        return formatMoney(value, currencyCode);
                    }
                }
            }
        };
        // first, collect all currencies and use them as series.
        let series = {};
        for (const i in data) {
            if (data.hasOwnProperty(i)) {
                let current = data[i];
                let code = current.currency_code;
                // only use native code when doing auto conversion.
                if (this.autoConversion) {
                    code = current.native_code;
                }

                if (!series.hasOwnProperty(code)) {
                    series[code] = {
                        name: code,
                        data: {},
                    };
                    window.categoryCurrencies.push(code);
                }
            }
        }
        // loop data again to add amounts.
        for (const i in data) {
            if (data.hasOwnProperty(i)) {
                let current = data[i];
                let code = current.currency_code;
                if (this.autoConversion) {
                    code = current.native_code;
                }

                // loop series, add 0 if not present or add actual amount.
                for (const ii in series) {
                    if (series.hasOwnProperty(ii)) {
                        let amount = 0.0;
                        if (code === ii) {
                            // this series' currency matches this column's currency.
                            amount = parseFloat(current.amount);
                            if (this.autoConversion) {
                                amount = parseFloat(current.native_amount);
                            }
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
                if (!options.xaxis.categories.includes(current.label)) {
                    options.xaxis.categories.push(current.label);
                }
            }
        }
        // loop the series and create Apex-compatible data sets.
        for (const i in series) {
            let current = {
                name: i,
                data: [],
            }
            for (const ii in series[i].data) {
                current.data.push(series[i].data[ii]);
            }
            options.series.push(current);
        }
        this.chartOptions = options;
    },
    drawChart() {
        if (null !== this.chart) {
            // chart already in place, refresh:
            this.chart.updateOptions(this.chartOptions);
        }
        if (null === this.chart) {
            this.chart = new ApexCharts(document.querySelector("#category-chart"), this.chartOptions);
            this.chart.render();
        }
        this.loading = false;

    },
    getFreshData() {
        const dashboard = new Dashboard();
        dashboard.dashboard(new Date(window.store.get('start')), new Date(window.store.get('end')), null).then((response) => {
            this.chartData = response.data;
            this.generateOptions(this.chartData);
            this.drawChart();
        });
    },

    loadChart() {
        if (true === this.loading) {
            return;
        }
        this.loading = true;
        if (null === this.chartData) {
            this.getFreshData();
        }
        if (null !== this.chartData) {
            this.generateOptions(this.chartData);
            this.drawChart();
        }

        this.loading = false;
    },
    init() {
        Promise.all([getVariable('autoConversion', false),]).then((values) => {
            // this.autoConversion = values[0];
            // this.loadChart();
        });
        window.store.observe('end', () => {
            // this.chartData = null;
            // this.loadChart();
        });
        window.store.observe('autoConversion', (newValue) => {
            // this.autoConversion = newValue;
            // this.loadChart();
        });
    },

});


