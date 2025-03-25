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
import Get from "../../api/v1/model/subscription/get.js";
import {format} from "date-fns";
import {getCacheKey} from "../../support/get-cache-key.js";
import {Chart} from "chart.js";
import formatMoney from "../../util/format-money.js";
import i18next from "i18next";

// let chart = null;
// let chartData = null;
let afterPromises = false;
let apiData = [];
let subscriptionData = {};

function downloadSubscriptions(params) {
    const getter = new Get();
    return getter.list(params)
        // first promise: parse the data:
        .then((response) => {
            let data = response.data.data;
            //console.log(data);
            for (let i in data) {
                if (data.hasOwnProperty(i)) {
                    let current = data[i];
                    //console.log(current);
                    if (current.attributes.active && current.attributes.pay_dates.length > 0) {
                        let objectGroupId = null === current.attributes.object_group_id ? 0 : current.attributes.object_group_id;
                        let objectGroupTitle = null === current.attributes.object_group_title ? i18next.t('firefly.default_group_title_name_plain') : current.attributes.object_group_title;
                        let objectGroupOrder = null === current.attributes.object_group_order ? 0 : current.attributes.object_group_order;
                        if (!subscriptionData.hasOwnProperty(objectGroupId)) {
                            subscriptionData[objectGroupId] = {
                                id: objectGroupId,
                                title: objectGroupTitle,
                                order: objectGroupOrder,
                                payment_info: {},
                                bills: [],
                            };
                        }
                        // TODO this conversion needs to be inside some kind of a parsing class.
                        let bill = {
                            id: current.id,
                            name: current.attributes.name,
                            // amount
                            amount_min: current.attributes.amount_min,
                            amount_max: current.attributes.amount_max,
                            amount: (parseFloat(current.attributes.amount_max) + parseFloat(current.attributes.amount_min)) / 2,
                            currency_code: current.attributes.currency_code,

                            // native amount
                            // native_amount_min: current.attributes.native_amount_min,
                            // native_amount_max: current.attributes.native_amount_max,
                            // native_amount: (parseFloat(current.attributes.native_amount_max) + parseFloat(current.attributes.native_amount_min)) / 2,
                            // native_currency_code: current.attributes.native_currency_code,

                            // paid transactions:
                            transactions: [],

                            // unpaid moments
                            pay_dates: current.attributes.pay_dates,
                            paid: current.attributes.paid_dates.length > 0,
                        };
                        // set variables
                        bill.expected_amount = formatMoney(bill.amount, bill.currency_code);
                        bill.expected_times = i18next.t('firefly.subscr_expected_x_times', {
                            times: current.attributes.pay_dates.length,
                            amount: bill.expected_amount
                        });

                        // add transactions (simpler version)
                        for (let iii in current.attributes.paid_dates) {
                            if (current.attributes.paid_dates.hasOwnProperty(iii)) {
                                const currentPayment = current.attributes.paid_dates[iii];
                                let percentage = 100;
                                // math: -100+(paid/expected)*100
                                if (params.convertToNative) {
                                    percentage = Math.round(-100 + ((parseFloat(currentPayment.native_amount) * -1) / parseFloat(bill.native_amount)) * 100);
                                }
                                if (!params.convertToNative) {
                                    percentage = Math.round(-100 + ((parseFloat(currentPayment.amount) * -1) / parseFloat(bill.amount)) * 100);
                                }
                                // TODO fix me
                                currentPayment.currency_code = 'EUR';
                                console.log('Currency code: "'+currentPayment+'"');
                                console.log(currentPayment);
                                let currentTransaction = {
                                    amount: formatMoney(currentPayment.amount, currentPayment.currency_code),
                                    percentage: percentage,
                                    date: format(new Date(currentPayment.date), 'PP'),
                                    foreign_amount: null,
                                };
                                if (null !== currentPayment.foreign_currency_code) {
                                    currentTransaction.foreign_amount =  currentPayment.foreign_amount;
                                    currentTransaction.foreign_currency_code = currentPayment.foreign_currency_code;
                                }

                                bill.transactions.push(currentTransaction);
                            }
                        }

                        subscriptionData[objectGroupId].bills.push(bill);
                        if (0 === current.attributes.paid_dates.length) {
                            // bill is unpaid, count the "pay_dates" and multiply with the "amount".
                            // since bill is unpaid, this can only be in currency amount and native currency amount.
                            const totalAmount = current.attributes.pay_dates.length * bill.amount;
                            // const totalNativeAmount = current.attributes.pay_dates.length * bill.native_amount;
                            // for bill's currency
                            if (!subscriptionData[objectGroupId].payment_info.hasOwnProperty(bill.currency_code)) {
                                subscriptionData[objectGroupId].payment_info[bill.currency_code] = {
                                    currency_code: bill.currency_code,
                                    paid: 0,
                                    unpaid: 0,
                                    native_currency_code: bill.native_currency_code,
                                    native_paid: 0,
                                    //native_unpaid: 0,
                                };
                            }
                            subscriptionData[objectGroupId].payment_info[bill.currency_code].unpaid += totalAmount;
                            //subscriptionData[objectGroupId].payment_info[bill.currency_code].native_unpaid += totalNativeAmount;
                        }

                        if (current.attributes.paid_dates.length > 0) {
                            for (let ii in current.attributes.paid_dates) {
                                if (current.attributes.paid_dates.hasOwnProperty(ii)) {
                                    // bill is paid!
                                    // since bill is paid, 3 possible currencies:
                                    // native, currency, foreign currency.
                                    // foreign currency amount (converted to native or not) will be ignored.
                                    let currentJournal = current.attributes.paid_dates[ii];
                                    // new array for the currency
                                    if (!subscriptionData[objectGroupId].payment_info.hasOwnProperty(currentJournal.currency_code)) {
                                        subscriptionData[objectGroupId].payment_info[currentJournal.currency_code] = {
                                            currency_code: bill.currency_code,
                                            paid: 0,
                                            unpaid: 0,
                                            // native_currency_code: bill.native_currency_code,
                                            // native_paid: 0,
                                            //native_unpaid: 0,
                                        };
                                    }
                                    const amount = parseFloat(currentJournal.amount) * -1;
                                    // const nativeAmount = parseFloat(currentJournal.native_amount) * -1;
                                    subscriptionData[objectGroupId].payment_info[currentJournal.currency_code].paid += amount;
                                    // subscriptionData[objectGroupId].payment_info[currentJournal.currency_code].native_paid += nativeAmount;
                                }
                            }
                        }
                    }
                }
            }
            // if next page, return the same function + 1 page:
            if (parseInt(response.data.meta.pagination.total_pages) > params.page) {
                params.page++;
                return downloadSubscriptions(params);
            }
            // otherwise return resolved promise:
            return Promise.resolve();
        });

}


export default () => ({
    loading: false,
    convertToNative: false,
    subscriptions: [],
    startSubscriptions() {
        this.loading = true;
        let start = new Date(window.store.get('start'));
        let end = new Date(window.store.get('end'));

        const cacheValid = window.store.get('cacheValid');
        let cachedData = window.store.get(getCacheKey('ds_sub_data', {start: start, end: end}));

        if (cacheValid && typeof cachedData !== 'undefined' && false) {
            console.error('cannot handle yet');
            return;
        }
        // reset subscription data
        subscriptionData = {};
        this.subscriptions = [];
        let params = {
            start: format(start, 'y-MM-dd'),
            end: format(end, 'y-MM-dd'),
            // convertToNative: this.convertToNative,
            page: 1
        };
        downloadSubscriptions(params).then(() => {
            let set = Object.values(subscriptionData);
            // convert subscriptionData to usable data (especially for the charts)
            for (let i in set) {
                if (set.hasOwnProperty(i)) {
                    let group = set[i];
                    const keys = Object.keys(group.payment_info);
                    // do some parsing here.
                    group.col_size = 1 === keys.length ? 'col-6 offset-3' : 'col-6';
                    group.chart_width = 1 === keys.length ? '50%' : '100%';
                    group.payment_length = keys.length;

                    // then add to array
                    this.subscriptions.push(group);
                    //console.log(group);
                }
            }

            // then assign to this.subscriptions.
            this.loading = false;
        });
    },
    drawPieChart(groupId, groupTitle, data) {
        let id = '#pie_' + groupId + '_' + data.currency_code;
        //console.log(data);
        const unpaidAmount =  data.unpaid;
        const paidAmount =  data.paid;
        const currencyCode =  data.currency_code;
        const chartData = {
            labels: [
                i18next.t('firefly.paid'),
                i18next.t('firefly.unpaid')
            ],
            datasets: [{
                label: i18next.t('firefly.subscriptions_in_group', {title: groupTitle}),
                data: [paidAmount, unpaidAmount],
                backgroundColor: [
                    'rgb(54, 162, 235)',
                    'rgb(255, 99, 132)',
                ],
                hoverOffset: 4
            }]
        };
        const config = {
            type: 'doughnut',
            data: chartData,
            options: {
                plugins: {
                    tooltip: {
                        callbacks: {
                            label: function (tooltipItem) {
                                return tooltipItem.dataset.label + ': ' + formatMoney(tooltipItem.raw, currencyCode);
                            },
                        },
                    },
                },
            }
        };
        var graph = Chart.getChart(document.querySelector(id));
        if (typeof graph !== 'undefined') {
            graph.destroy();
        }
        new Chart(document.querySelector(id), config);
    },

    init() {
        Promise.all([getVariable('convertToNative', false)]).then((values) => {
            this.convertToNative = values[0];
            afterPromises = true;

            if (false === this.loading) {
                this.startSubscriptions();
            }


        });
        window.store.observe('end', () => {
            if (!afterPromises) {
                return;
            }
            if (false === this.loading) {
                this.startSubscriptions();
            }
        });
        window.store.observe('convertToNative', (newValue) => {
            if (!afterPromises) {
                return;
            }
            this.convertToNative = newValue;
            if (false === this.loading) {
                this.startSubscriptions();
            }
        });
    },

});


