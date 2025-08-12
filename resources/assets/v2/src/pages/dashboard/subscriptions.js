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
let convertToPrimary = false;

function addObjectGroupInfo(data) {
    let objectGroupId = parseInt(data.object_group_id);
    if (!subscriptionData.hasOwnProperty(objectGroupId)) {
        subscriptionData[objectGroupId] = {
            id: objectGroupId,
            title: null === data.object_group_title ? i18next.t('firefly.default_group_title_name_plain') : data.object_group_title,
            order: parseInt(data.object_group_order),
            payment_info: {},
            bills: [],
        };
    }
}

function parsePayDates(list) {
    let newList = [];
    for(let i in list) {
        if (list.hasOwnProperty(i)) {
            let current = list[i];
            // convert to date object:
            newList.push(new Date(current));
        }
    }
    return newList;
}

function parseBillInfo(data) {
    let result = {
        id: data.id,
        name: data.attributes.name,
        amount_min: data.attributes.amount_min,
        amount_max: data.attributes.amount_max,
        amount: (parseFloat(data.attributes.amount_max) + parseFloat(data.attributes.amount_min)) / 2,
        currency_code: data.attributes.currency_code,
        // paid transactions:
        transactions: [],
        // unpaid moments
        pay_dates: parsePayDates(data.attributes.pay_dates),
        paid: data.attributes.paid_dates.length > 0,
    };
    if(convertToPrimary) {
        result.currency_code = data.attributes.primary_currency_code;
    }


    // set variables
    result.expected_amount = formatMoney(result.amount, result.currency_code);
    result.expected_times = i18next.t('firefly.subscr_expected_x_times', {
        times: data.attributes.pay_dates.length,
        amount: result.expected_amount
    });
    // console.log(result);
    return result;
}

function parsePaidTransactions(paid_dates, bill) {
    if( !paid_dates || paid_dates.length < 1) {
        return [];
    }
    let result = [];
    // add transactions (simpler version)
    for (let i in paid_dates) {
        if (paid_dates.hasOwnProperty(i)) {
            const currentPayment = paid_dates[i];
            // console.log(currentPayment);
            // math: -100+(paid/expected)*100
            let percentage = Math.round(-100 + ((parseFloat(currentPayment.amount) ) / parseFloat(bill.amount)) * 100);
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

            result.push(currentTransaction);
        }
    }
    return result;
}

function isInRange(bill) {
    let start = new Date(window.store.get('start'));
    let end = new Date(window.store.get('end'));
    for(let i in bill.pay_dates) {
        if (bill.pay_dates.hasOwnProperty(i)) {
            let currentDate = bill.pay_dates[i];
            //console.log(currentDate);
            if (currentDate >= start && currentDate <= end) {
                return true;
            }
        }
    }
    return false;
}

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
                    if (current.attributes.active && current.attributes.pay_dates.length > 0) {
                        // create or update object group
                        let objectGroupId = parseInt(current.attributes.object_group_id);
                        addObjectGroupInfo(current.attributes);

                        // create and update the bill.
                        let bill = parseBillInfo(current);

                        // if not yet paid, and pay_dates is not in current rage, ignore it.
                        if (false === bill.paid && !isInRange(bill)) {
                            console.warn('Bill "'+bill.name+'" is not paid and not in range, ignoring: ');
                            continue;
                        }


                        bill.transactions = parsePaidTransactions(current.attributes.paid_dates, bill);

                        subscriptionData[objectGroupId].bills.push(bill);
                        if (false === bill.paid) {
                            // bill is unpaid, count the "pay_dates" and multiply with the "amount".
                            // since bill is unpaid, this can only be in currency amount and primary currency amount.
                            const totalAmount = current.attributes.pay_dates.length * bill.amount;
                            // for bill's currency
                            if (!subscriptionData[objectGroupId].payment_info.hasOwnProperty(bill.currency_code)) {
                                subscriptionData[objectGroupId].payment_info[bill.currency_code] = {
                                    currency_code: bill.currency_code,
                                    paid: 0,
                                    unpaid: 0,
                                };
                            }

                            subscriptionData[objectGroupId].payment_info[bill.currency_code].unpaid += totalAmount;
                        }

                        if (current.attributes.paid_dates.length > 0) {
                            for (let ii in current.attributes.paid_dates) {
                                if (current.attributes.paid_dates.hasOwnProperty(ii)) {
                                    // bill is paid!
                                    // since bill is paid, 3 possible currencies:
                                    let currentJournal = current.attributes.paid_dates[ii];
                                    // new array for the currency
                                    if (!subscriptionData[objectGroupId].payment_info.hasOwnProperty(currentJournal.currency_code)) {
                                        subscriptionData[objectGroupId].payment_info[currentJournal.currency_code] = {
                                            currency_code: bill.currency_code,
                                            paid: 0,
                                            unpaid: 0,
                                        };
                                    }
                                    const amount = parseFloat(currentJournal.amount) * -1;
                                    subscriptionData[objectGroupId].payment_info[currentJournal.currency_code].paid += amount;
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
    convertToPrimary: false,
    subscriptions: [],
    formatMoney(amount, currencyCode) {
        return formatMoney(amount, currencyCode);
    },
    eventListeners: {
        ['@convert-to-primary.window'](event){
            console.log('I heard that! (dashboard/subscriptions)');
            this.convertToPrimary = event.detail;
            convertToPrimary = event.detail;
            this.startSubscriptions();
        }
    },

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
            // convertToPrimary: this.convertToPrimary,
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
            console.log('Subscriptions: ', this.subscriptions);

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
        Promise.all([getVariable('convert_to_primary', false)]).then((values) => {
            this.convertToPrimary = values[0];
            convertToPrimary = values[0];
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
        window.store.observe('convert_to_primary', (newValue) => {
            if (!afterPromises) {
                return;
            }
            this.convertToPrimary = newValue;
            if (false === this.loading) {
                this.startSubscriptions();
            }
        });
    },

});


