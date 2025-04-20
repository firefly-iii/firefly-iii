/*
 * show.js
 * Copyright (c) 2024 james@firefly-iii.org.
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
 * along with this program.  If not, see https://www.gnu.org/licenses/.
 */

import '../../boot/bootstrap.js';
import dates from "../shared/dates.js";
import i18next from "i18next";
import {format} from "date-fns";
import formatMoney from "../../util/format-money.js";

import '../../css/grid-ff3-theme.css';
import Get from "../../api/v1/model/transaction/get.js";

let index = function () {
    return {
        // notifications
        notifications: {
            error: {
                show: false, text: '', url: '',
            }, success: {
                show: false, text: '', url: '',
            }, wait: {
                show: false, text: '',

            }
        },
        transactions: [],
        totalPages: 1,
        perPage: 50,
        page: 1,
        // available columns:
        tableColumns: {
            description: {
                enabled: true
            },
            source: {
                enabled: true
            },
            destination: {
                enabled: true
            },
            amount: {
                enabled: true
            },
        },

        table: null,

        formatMoney(amount, currencyCode) {
            return formatMoney(amount, currencyCode);
        },
        format(date) {
            return format(date, i18next.t('config.date_time_fns'));
        },
        init() {
            // TODO need date range.
            // TODO handle page number
            this.getTransactions(this.page);``

            // Your Javascript code to create the grid
            // dataTable = createGrid(document.querySelector('#grid'), gridOptions);


        },
        getTransactions(page) {
            this.notifications.wait.show = true;
            this.notifications.wait.text = i18next.t('firefly.wait_loading_data')
            this.transactions = [];
            const urlParts = window.location.href.split('/');
            const type = urlParts[urlParts.length - 1];
            let getter = new Get();

            getter.list({page: page, type: type}).then(response => {
                this.parseTransactions(response.data.data)

                // set meta data
                this.totalPages = response.data.meta.pagination.total_pages;
                this.perPage = response.data.meta.pagination.per_page;
                this.page = response.data.meta.pagination.current_page;
            }).catch(error => {
                // to do this is auto generated
                this.notifications.wait.show = false;
                this.notifications.error.show = true;
                this.notifications.error.text = error.response.data.message;
            });
        },
        previousPage() {
            if(this.page > 1) {
                this.page--;
                }
            this.getTransactions(this.page);
        },
        nextPage() {
            if(this.page < this.totalPages) {
                this.page++;
            }
            this.getTransactions(this.page);
        },
        gotoPage(i) {
            if(i > 0 && i <= this.totalPages) {
                this.page = i;
            }
            this.getTransactions(this.page);
        },

        parseTransactions(data) {
            // no parse, just save
            for (let i in data) {
                if (data.hasOwnProperty(i)) {
                    let current = data[i];
                    let isSplit = current.attributes.transactions.length > 1;
                    let firstSplit = true;

                    // foreach on transactions, no matter how many.
                    for (let ii in current.attributes.transactions) {
                        if (current.attributes.transactions.hasOwnProperty(ii)) {
                            let transaction = current.attributes.transactions[ii];


                            transaction.split = isSplit;
                            transaction.icon = 'fa fa-solid fa-arrow-left';
                            transaction.firstSplit = firstSplit;
                            transaction.group_title = current.attributes.group_title;
                            transaction.id = current.id;
                            transaction.created_at = current.attributes.created_at;
                            transaction.updated_at = current.attributes.updated_at;
                            transaction.user = current.attributes.user;
                            transaction.user_group = current.attributes.user_group;

                            // set firstSplit = false for next run if applicable.
                            firstSplit = false;
                            //console.log(transaction);
                            this.transactions.push(transaction);
                            //this.gridOptions.rowData.push(transaction);
                        }
                    }
                }
            }
            // only now, disable wait thing.
            this.notifications.wait.show = false;
            console.log('refresh!');
            //this.table.refreshCells();

        },
    }
}

let comps = {index, dates};

function loadPage() {
    Object.keys(comps).forEach(comp => {
        console.log(`Loading page component "${comp}"`);
        let data = comps[comp]();
        Alpine.data(comp, () => data);
    });
    Alpine.start();
}

// wait for load until bootstrapped event is received.
document.addEventListener('firefly-iii-bootstrapped', () => {
    console.log('Loaded through event listener.');
    loadPage();
});
// or is bootstrapped before event is triggered.
if (window.bootstrapped) {
    console.log('Loaded through window variable.');
    loadPage();
}
