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

import '@ag-grid-community/styles/ag-grid.css';
import '@ag-grid-community/styles/ag-theme-alpine.css';
import '../../css/grid-ff3-theme.css';
import Get from "../../api/v2/model/account/get.js";
import GenericEditor from "../../support/editable/GenericEditor.js";
import Put from "../../api/v2/model/account/put.js";

// set type from URL
const urlParts = window.location.href.split('/');
const type = urlParts[urlParts.length - 1];

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
        }, totalPages: 1, page: 1, // available columns:
        tableColumns: {
            name: {
                enabled: true
            },
        },
        editors: {},
        sortingColumn: '',
        sortDirection: '',
        accounts: [],

        sort(column) {
            this.sortingColumn = column;
            this.sortDirection = this.sortDirection === 'asc' ? 'desc' : 'asc';
            this.loadAccounts();
            return false;
        },

        formatMoney(amount, currencyCode) {
            return formatMoney(amount, currencyCode);
        },

        format(date) {
            return format(date, i18next.t('config.date_time_fns'));
        },

        init() {
            this.notifications.wait.show = true;
            this.notifications.wait.text = i18next.t('firefly.wait_loading_data')
            this.loadAccounts();
        },
        submitInlineEdit(e) {
            e.preventDefault();
            const newTarget = e.currentTarget;
            const index = newTarget.dataset.index;
            const newValue = document.querySelectorAll('[data-index="'+index+'input"]')[0].value ?? '';
            if('' === newValue) {
                return;
            }
            // submit the field in an update thing?
            const fieldName = this.editors[index].options.field;
            const params = {};
            params[fieldName] = newValue;
            console.log(params);
            console.log('New value is ' + newValue + ' for account #' + this.editors[index].options.id);
            (new Put()).put(this.editors[index].options.id, params);
        },
        cancelInlineEdit(e) {
            const newTarget = e.currentTarget;
            const index = newTarget.dataset.index;
            this.editors[index].cancel();
        },
        triggerEdit(e) {
            const target = e.currentTarget;
            const index = target.dataset.index;
            // get parent:
            this.editors[index] = new GenericEditor();
            this.editors[index].setElement(target);
            this.editors[index].init();
            this.editors[index].replace();
        },
        loadAccounts() {
            this.notifications.wait.show = true;
            this.notifications.wait.text = i18next.t('firefly.wait_loading_data')
            this.accounts = [];
            // sort instructions
            // &sorting[0][column]=description&sorting[0][direction]=asc
            const sorting = [{column: this.sortingColumn, direction: this.sortDirection}];
            // one page only.
            (new Get()).index({sorting: sorting, type: type, page: this.page}).then(response => {
                for (let i = 0; i < response.data.data.length; i++) {
                    if (response.data.data.hasOwnProperty(i)) {
                        let current = response.data.data[i];
                        let account = {
                            id: parseInt(current.id),
                            active: current.attributes.active,
                            name: current.attributes.name,
                            type: current.attributes.type,
                            role: current.attributes.account_role,
                            iban: null === current.attributes.iban ? '' : current.attributes.iban.match(/.{1,4}/g).join(' '),
                            account_number: null === current.attributes.account_number ? '' : current.attributes.account_number,
                            current_balance: current.attributes.current_balance,
                            currency_code: current.attributes.currency_code,
                            native_current_balance: current.attributes.native_current_balance,
                            native_currency_code: current.attributes.native_currency_code,
                            last_activity: null === current.attributes.last_activity ? '' : format(new Date(current.attributes.last_activity), 'P'),
                        };
                        this.accounts.push(account);
                    }
                }
                this.notifications.wait.show = false;
                // add click trigger thing.
            });
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
