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
import ApexCharts from "apexcharts";
import formatMoney from "../../util/format-money.js";

window.budgetCurrencies = [];
export default () => ({
    loading: false,
    chart: null,
    autoConversion: false,
    chartData: null,
    chartOptions: null,
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
    drawChart() {
        if (null !== this.chart) {
            // chart already in place, refresh:
            this.chart.updateOptions(this.chartOptions);
        }
        if (null === this.chart) {
            this.chart = new ApexCharts(document.querySelector("#budget-chart"), this.chartOptions);
            this.chart.render();
        }
    },
    getFreshData() {
        const dashboard = new Dashboard();
        dashboard.dashboard(new Date(window.store.get('start')), new Date(window.store.get('end')), null).then((response) => {
            this.chartData = response.data;
            this.generateOptions(this.chartData);
            this.drawChart();
        });
    },
    generateOptions(data) {
        window.budgetCurrencies = [];
        let options = {
            legend: {show: false},
            series: [{
                name: 'Spent',
                data: []
            }, {
                name: 'Left',
                data: []
            }, {
                name: 'Overspent',
                data: []
            }],
            chart: {
                type: 'bar',
                height: 400,
                stacked: true,
                toolbar: {tools: {zoom: false, download: false, pan: false}},
                zoom: {
                    enabled: true
                }
            },
            responsive: [{
                breakpoint: 480,
                options: {
                    legend: {
                        position: 'bottom',
                        offsetX: -10,
                        offsetY: 0
                    }
                }
            }],
            plotOptions: {
                bar: {
                    horizontal: false,
                    borderRadius: 10,
                    dataLabels: {
                        total: {
                            enabled: true,
                            // style: {
                            //     fontSize: '13px',
                            //     fontWeight: 900
                            // },
                            formatter: function (val, opt) {
                                let index = 0;
                                if (typeof opt === 'object') {
                                    index = opt.dataPointIndex; // this is the "category name + currency" index
                                }
                                let currencyCode = window.budgetCurrencies[index] ?? 'EUR';
                                return formatMoney(val, currencyCode);
                            }
                        }
                    }
                },
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
            xaxis: {
                categories: []
            },
            fill: {
                opacity: 0.8
            },
            dataLabels: {
                formatter: function (val, opt) {
                    let index = 0;
                    if (typeof opt === 'object') {
                        index = opt.dataPointIndex; // this is the "category name + currency" index
                    }
                    let currencyCode = window.budgetCurrencies[index] ?? 'EUR';
                    return formatMoney(val, currencyCode);
                },
            }
        };


        for (const i in data) {
            if (data.hasOwnProperty(i)) {
                let current = data[i];
                // convert to EUR yes no?
                let label = current.label + ' (' + current.currency_code + ')';
                options.xaxis.categories.push(label);
                if (this.autoConversion) {
                    window.budgetCurrencies.push(current.native_code);

                    // series 0: spent
                    options.series[0].data.push(parseFloat(current.native_entries.spent) * -1);
                    // series 1: left
                    options.series[1].data.push(parseFloat(current.native_entries.left));
                    // series 2: overspent
                    options.series[2].data.push(parseFloat(current.native_entries.overspent));
                }
                if (!this.autoConversion) {
                    window.budgetCurrencies.push(current.currency_code);
                    // series 0: spent
                    options.series[0].data.push(parseFloat(current.entries.spent) * -1);
                    // series 1: left
                    options.series[1].data.push(parseFloat(current.entries.left));
                    // series 2: overspent
                    options.series[2].data.push(parseFloat(current.entries.overspent));
                }

            }
        }
        this.chartOptions = options;
    },


    init() {
        Promise.all([getVariable('autoConversion', false),]).then((values) => {
            this.autoConversion = values[0];
            this.loadChart();
        });
        // todo the charts don't need to reload from server if the autoConversion value changes.
        window.store.observe('end', () => {
            this.chartData = null;
            this.loadChart();
        });
        window.store.observe('autoConversion', (newValue) => {
            this.autoConversion = newValue;
            this.loadChart();
        });
    },

});


