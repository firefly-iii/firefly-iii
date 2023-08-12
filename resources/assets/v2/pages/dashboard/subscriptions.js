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
import Get from "../../api/v2/model/subscription/get.js";
import {getDefaultChartSettings} from "../../support/default-chart-settings.js";
import {format} from "date-fns";
import {Chart} from 'chart.js';
import {I18n} from "i18n-js";
import {loadTranslations} from "../../support/load-translations.js";

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
        chart = new Chart(document.querySelector("#subscriptions-chart"), options);
    },
    getFreshData() {
        const getter = new Get();
        let params = {
            start: format(new Date(window.store.get('start')), 'y-MM-dd'),
            end: format(new Date(window.store.get('end')), 'y-MM-dd')
        };

        getter.paid(params).then((response) => {
            let paidData = response.data;
            getter.unpaid(params).then((response) => {
                let unpaidData = response.data;
                let chartData = {paid: paidData, unpaid: unpaidData};
                this.drawChart(this.generateOptions(chartData));
                this.loading = false;
            });
        });
    },
    generateOptions(data) {
        let options = getDefaultChartSettings('pie');
        // console.log(data);
        options.data.labels = [i18n.t('firefly.paid'), i18n.t('firefly.unpaid')];
        options.data.datasets = [];
        let collection = {};
        for (let i in data.paid) {
            if (data.paid.hasOwnProperty(i)) {
                let current = data.paid[i];
                let currencyCode = this.autoConversion ? current.native_code : current.currency_code;
                let amount = this.autoConversion ? current.native_sum : current.sum;
                if (!collection.hasOwnProperty(currencyCode)) {
                    collection[currencyCode] = {
                        paid: 0,
                        unpaid: 0,
                    };
                }
                // in case of paid, add to "paid":
                collection[currencyCode].paid += (parseFloat(amount) * -1);
            }
        }
        // unpaid
        for (let i in data.unpaid) {
            if (data.unpaid.hasOwnProperty(i)) {
                let current = data.unpaid[i];
                let currencyCode = this.autoConversion ? current.native_code : current.currency_code;
                let amount = this.autoConversion ? current.native_sum : current.sum;
                if (!collection.hasOwnProperty(currencyCode)) {
                    collection[currencyCode] = {
                        paid: 0,
                        unpaid: 0,
                    };
                }
                // console.log(current);
                // in case of paid, add to "paid":
                collection[currencyCode].unpaid += parseFloat(amount);
            }
        }
        for (let currencyCode in collection) {
            if (collection.hasOwnProperty(currencyCode)) {
                let current = collection[currencyCode];
                options.data.datasets.push(
                    {
                        label: currencyCode,
                        data: [current.paid, current.unpaid],
                        backgroundColor: [
                            'rgb(54, 162, 235)', // green (paid)
                            'rgb(255, 99, 132)', // red (unpaid_
                        ],
                        //hoverOffset: 4
                    }
                )
            }
        }

        return options;
    },


    init() {
        // console.log('subscriptions init');
        Promise.all([getVariable('autoConversion', false), getVariable('language', 'en-US')]).then((values) => {
            // console.log('subscriptions after promises');
            this.autoConversion = values[0];
            afterPromises = true;

            i18n = new I18n();
            i18n.locale = values[1];
            loadTranslations(i18n, values[1]);


            if (false === this.loading) {
                this.loadChart();
            }
        });
        window.store.observe('end', () => {
            if (!afterPromises) {
                return;
            }
            // console.log('subscriptions observe end');
            if (false === this.loading) {
                this.chartData = null;
                this.loadChart();
            }
        });
        window.store.observe('autoConversion', (newValue) => {
            if (!afterPromises) {
                return;
            }
            // console.log('subscriptions observe autoConversion');
            this.autoConversion = newValue;
            if (false === this.loading) {
                this.loadChart();
            }
        });
    },

});


