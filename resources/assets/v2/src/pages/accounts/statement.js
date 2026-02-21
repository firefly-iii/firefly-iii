/*
 * statement.js
 * Copyright (c) 2026 james@firefly-iii.org
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

import '../../boot/bootstrap.js';
import i18next from "i18next";
import {format, parseISO, subMonths, addMonths} from "date-fns";
import formatMoney from "../../util/format-money.js";
import Statement from "../../api/v1/model/account/statement.js";

let statement = function () {
    return {
        notifications: {
            error: {show: false, text: '', url: ''},
            success: {show: false, text: '', url: ''},
            wait: {show: false, text: ''},
        },

        accountId: 0,
        accountName: '',
        date: '',
        currencyCode: '',

        statementInfo: {
            start: '',
            end: '',
            closing_day: 0,
            due_date: null,
            total_charges: '0.00',
            total_payments: '0.00',
            balance: '0.00',
        },

        transactions: [],
        totalPages: 1,
        perPage: 50,
        page: 1,

        tableColumns: {
            description: {enabled: true},
            source: {enabled: true},
            destination: {enabled: true},
            amount: {enabled: true},
            date: {enabled: true},
            category: {enabled: true},
        },

        formatMoney(amount, currencyCode) {
            return formatMoney(amount, currencyCode);
        },

        formatDate(dateStr) {
            if (!dateStr) return '';
            return format(parseISO(dateStr), 'yyyy-MM-dd');
        },

        init() {
            this.accountId = parseInt(document.querySelector('meta[name="account-id"]')?.content || '0');
            this.date = document.querySelector('meta[name="statement-date"]')?.content || '';
            this.loadStatement();
        },

        loadStatement() {
            this.notifications.wait.show = true;
            this.notifications.wait.text = i18next.t('firefly.wait_loading_data');
            this.transactions = [];

            let getter = new Statement();
            let params = {page: this.page};
            if (this.date) {
                params.date = this.date;
            }

            getter.get(this.accountId, params).then(response => {
                this.statementInfo = response.data.statement;
                this.parseTransactions(response.data.data);
                if (response.data.meta?.pagination) {
                    this.totalPages = response.data.meta.pagination.total_pages;
                    this.perPage = response.data.meta.pagination.per_page;
                    this.page = response.data.meta.pagination.current_page;
                }
                this.notifications.wait.show = false;
            }).catch(error => {
                this.notifications.wait.show = false;
                this.notifications.error.show = true;
                this.notifications.error.text = error.response?.data?.message || 'Failed to load statement.';
            });
        },

        parseTransactions(data) {
            for (let i in data) {
                if (data.hasOwnProperty(i)) {
                    let current = data[i];
                    let isSplit = current.attributes.transactions.length > 1;
                    let firstSplit = true;

                    for (let ii in current.attributes.transactions) {
                        if (current.attributes.transactions.hasOwnProperty(ii)) {
                            let transaction = current.attributes.transactions[ii];
                            transaction.split = isSplit;
                            transaction.firstSplit = firstSplit;
                            transaction.group_title = current.attributes.group_title;
                            transaction.id = current.id;
                            firstSplit = false;
                            this.transactions.push(transaction);
                        }
                    }
                }
            }
        },

        previousStatement() {
            if (!this.statementInfo.start) return;
            let prev = subMonths(parseISO(this.statementInfo.start), 1);
            this.date = format(prev, 'yyyy-MM-dd');
            this.page = 1;
            this.loadStatement();
            this.updateUrl();
        },

        nextStatement() {
            if (!this.statementInfo.end) return;
            let next = addMonths(parseISO(this.statementInfo.end), 1);
            this.date = format(next, 'yyyy-MM-dd');
            this.page = 1;
            this.loadStatement();
            this.updateUrl();
        },

        updateUrl() {
            let newUrl = './accounts/statement/' + this.accountId + '/' + this.date;
            window.history.pushState({}, '', newUrl);
        },

        previousPage() {
            if (this.page > 1) {
                this.page--;
                this.loadStatement();
            }
        },

        nextPage() {
            if (this.page < this.totalPages) {
                this.page++;
                this.loadStatement();
            }
        },

        gotoPage(i) {
            if (i > 0 && i <= this.totalPages) {
                this.page = i;
                this.loadStatement();
            }
        },
    }
}

let comps = {statement};

function loadPage() {
    Object.keys(comps).forEach(comp => {
        console.log(`Loading page component "${comp}"`);
        let data = comps[comp]();
        Alpine.data(comp, () => data);
    });
    Alpine.start();
}

document.addEventListener('firefly-iii-bootstrapped', () => {
    console.log('Loaded through event listener.');
    loadPage();
});
if (window.bootstrapped) {
    console.log('Loaded through window variable.');
    loadPage();
}
