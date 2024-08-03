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

import Get from "../../api/v2/model/account/get.js";
import Put from "../../api/v2/model/account/put.js";
import AccountRenderer from "../../support/renderers/AccountRenderer.js";
import {showInternalsButton} from "../../support/page-settings/show-internals-button.js";
import {showWizardButton} from "../../support/page-settings/show-wizard-button.js";
import {setVariable} from "../../store/set-variable.js";
import {getVariables} from "../../store/get-variables.js";
import pageNavigation from "../../support/page-navigation.js";


// set type from URL
const beforeQuery = window.location.href.split('?');
const urlParts = beforeQuery[0].split('/');
const type = urlParts[urlParts.length - 1];

let sortingColumn = '';
let sortDirection = '';
let page = 1;

// get sort parameters
const params = new Proxy(new URLSearchParams(window.location.search), {
    get: (searchParams, prop) => searchParams.get(prop),
});
sortingColumn = params.column ?? '';
sortDirection = params.direction ?? '';
page = parseInt(params.page ?? 1);


showInternalsButton();
showWizardButton();

// TODO currency conversion
// TODO page cleanup and recycle for transaction lists.

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
        totalPages: 1,
        page: 1,
        pageUrl: '',
        filters: {
            active: null,
            name: null,
        },
        pageOptions: {
            isLoading: true,
            groupedAccounts: true,
            sortingColumn: sortingColumn,
            sortDirection: sortDirection,
        },

        // available columns:
        // visible is hard coded, enabled is user-configurable.
        tableColumns: {
            drag_and_drop: {
                visible: true,
                enabled: true,
            },
            active: {
                visible: true,
                enabled: true,
            },
            name: {
                visible: true,
                enabled: true,
            },
            type: {
                visible: type === 'asset',
                enabled: true,
            },
            liability_type: {
                visible: type === 'liabilities',
                enabled: true,
            },
            liability_direction: {
                visible: type === 'liabilities',
                enabled: true,
            },
            liability_interest: {
                visible: type === 'liabilities',
                enabled: true,
            },
            number: {
                visible: true,
                enabled: true,
            },
            current_balance: {
                visible: type !== 'liabilities',
                enabled: true,
            },
            amount_due: {
                visible: type === 'liabilities',
                enabled: true,
            },
            last_activity: {
                visible: true,
                enabled: true,
            },
            balance_difference: {
                visible: true,
                enabled: true,
            },
            menu: {
                visible: true,
                enabled: true,
            },
        },
        editors: {},
        accounts: [],
        lastClickedFilter: '',
        lastFilterInput: '',
        goToPage(page) {
            this.page = page;
            this.loadAccounts();
        },
        applyFilter() {
            this.filters[this.lastClickedFilter] = this.lastFilterInput;
            this.page = 1;
            setVariable(this.getPreferenceKey('filters'), this.filters);

            // hide modal
            window.bootstrap.Modal.getInstance(document.getElementById('filterModal')).hide();
            this.loadAccounts();
        },
        saveGroupedAccounts() {
            setVariable(this.getPreferenceKey('grouped'), this.pageOptions.groupedAccounts);
            this.page = 1;
            this.loadAccounts();
        },
        removeFilter(field) {
            this.filters[field] = null;
            this.page = 1;
            setVariable(this.getPreferenceKey('filters'), this.filters);
            this.loadAccounts();
        },
        hasFilters() {
            return this.filters.name !== null;
        },
        showFilterDialog(field) {
            this.lastFilterInput = this.filters[field] ?? '';
            this.lastClickedFilter = field;
            document.getElementById('filterInput').focus();
        },
        accountRole(roleName) {
            return i18next.t('firefly.account_role_' + roleName);
        },
        getPreferenceKey(name) {
            return 'acc_index_' + type + '_' + name;
        },
        pageNavigation() {
            return pageNavigation(this.totalPages, this.page, this.generatePageUrl(false));
        },

        sort(column) {
            this.page = 1;
            this.pageOptions.sortingColumn = column;
            this.pageOptions.sortDirection = this.pageOptions.sortDirection === 'asc' ? 'desc' : 'asc';

            this.updatePageUrl();

            // get sort column
            setVariable(this.getPreferenceKey('sc'), this.pageOptions.sortingColumn);
            setVariable(this.getPreferenceKey('sd'), this.pageOptions.sortDirection);

            this.loadAccounts();
            return false;
        },
        updatePageUrl() {
            this.pageUrl = this.generatePageUrl(true);

            window.history.pushState({}, "", this.pageUrl);
        },
        generatePageUrl(includePageNr) {
            let url = './accounts/' + type + '?column=' + this.pageOptions.sortingColumn + '&direction=' + this.pageOptions.sortDirection + '&page=';
            if (includePageNr) {
                return url + this.page
            }
            return url;
        },

        formatMoney(amount, currencyCode) {
            return formatMoney(amount, currencyCode);
        },

        format(date) {
            return format(date, i18next.t('config.date_time_fns'));
        },
        saveColumnSettings() {
            let newSettings = {};
            for (let key in this.tableColumns) {
                if (this.tableColumns.hasOwnProperty(key)) {
                    newSettings[key] = this.tableColumns[key].enabled;
                }
            }
            console.log('New settings', newSettings);
            setVariable(this.getPreferenceKey('columns'), newSettings);
        },

        init() {
            // modal filter thing
            const myModalEl = document.getElementById('filterModal')
            myModalEl.addEventListener('shown.bs.modal', event => {
                document.getElementById('filterInput').focus();
            })


            // some opts
            this.pageOptions.isLoading = true;
            this.notifications.wait.show = true;
            this.page = page;
            this.notifications.wait.text = i18next.t('firefly.wait_loading_data');

            // start by collecting all preferences, create + put in the local store.
            getVariables([
                {name: this.getPreferenceKey('columns'), default: {"drag_and_drop": false}},
                {name: this.getPreferenceKey('sc'), default: ''},
                {name: this.getPreferenceKey('sd'), default: ''},
                {name: this.getPreferenceKey('filters'), default: this.filters},
                {name: this.getPreferenceKey('grouped'), default: true},
            ]).then((res) => {
                // process columns:
                for (let k in res[0]) {
                    if (res[0].hasOwnProperty(k) && this.tableColumns.hasOwnProperty(k)) {
                        this.tableColumns[k].enabled = res[0][k] ?? true;
                    }
                }

                // process sorting column:
                this.pageOptions.sortingColumn = '' === this.pageOptions.sortingColumn ? res[1] : this.pageOptions.sortingColumn;

                // process sort direction
                this.pageOptions.sortDirection = '' === this.pageOptions.sortDirection ? res[2] : this.pageOptions.sortDirection;

                // filters
                for (let k in res[3]) {
                    if (res[3].hasOwnProperty(k) && this.filters.hasOwnProperty(k)) {
                        this.filters[k] = res[3][k];
                    }
                }

                // group accounts
                this.pageOptions.groupedAccounts = res[4];

                this.loadAccounts();
            });
        },
        saveActiveFilter(e) {
            this.page = 1;
            if ('both' === e.currentTarget.value) {
                this.filters.active = null;
            }
            if ('active' === e.currentTarget.value) {
                this.filters.active = true;
            }
            if ('inactive' === e.currentTarget.value) {
                this.filters.active = false;
            }
            setVariable(this.getPreferenceKey('filters'), this.filters);
            this.loadAccounts();
        },
        renderObjectValue(field, account) {
            let renderer = new AccountRenderer();
            if ('name' === field) {
                return renderer.renderName(account);
            }
        },
        submitInlineEdit(e) {
            e.preventDefault();
            const newTarget = e.currentTarget;
            const index = newTarget.dataset.index;
            const fieldName = newTarget.dataset.field;
            const accountId = newTarget.dataset.id;
            // need to find the input thing
            console.log('Clicked edit button for account on index #' + index + ' and field ' + fieldName);
            const querySelector = 'input[data-field="' + fieldName + '"][data-index="' + index + '"]';
            console.log(querySelector);
            const newValue = document.querySelectorAll(querySelector)[0].value ?? '';
            if ('' === newValue) {
                return;
            }
            console.log('new field name is ' + fieldName + '=' + newValue + ' for account #' + newTarget.dataset.id);
            const params = {};
            params[fieldName] = newValue;
            (new Put()).put(accountId, params);

            // update value, should auto render correctly!
            this.accounts[index][fieldName] = newValue;
            this.accounts[index].nameEditorVisible = false;
        },
        cancelInlineEdit(e) {
            const newTarget = e.currentTarget;
            const index = newTarget.dataset.index;
            this.accounts[index].nameEditorVisible = false;
        },
        triggerEdit(e) {
            const target = e.currentTarget;
            const index = target.dataset.index;
            const id = target.dataset.id;
            console.log('Index of this row is ' + index + ' and ID is ' + id);
            this.accounts[index].nameEditorVisible = true;
        },
        loadAccounts() {
            this.pageOptions.isLoading = true;
            // sort instructions (only one column)
            let sorting = this.pageOptions.sortingColumn;
            if('ASC' === this.pageOptions.sortDirection) {
                sorting = '-' + sorting;
            }
            //const sorting = [{column: this.pageOptions.sortingColumn, direction: this.pageOptions.sortDirection}];

            // filter instructions
            let filters = [];
            for (let k in this.filters) {
                if (this.filters.hasOwnProperty(k) && null !== this.filters[k]) {
                    filters.push({column: k, filter: this.filters[k]});
                }
            }

            // get start and end from the store:
            const start = new Date(window.store.get('start'));
            const end = new Date(window.store.get('end'));
            const today = new Date();

            let params = {
                sorting: sorting,
                filters: filters,
                // today: today,
                // type: type,
                page: {number: this.page},
                startPeriod: start,
                endPeriod: end
            };

            if (!this.tableColumns.balance_difference.enabled) {
                delete params.start;
                delete params.end;
            }
            this.accounts = [];
            let groupedAccounts = {};
            // one page only.o
            (new Get()).index(params).then(response => {
                console.log(response);
                this.totalPages = response.meta.lastPage;
                for (let i = 0; i < response.data.length; i++) {
                    if (response.data.hasOwnProperty(i)) {
                        let current = response.data[i];
                        let account = {
                            id: parseInt(current.id),
                            active: current.attributes.active,
                            name: current.attributes.name,
                            nameEditorVisible: false,
                            type: current.attributes.type,
                            role: current.attributes.account_role,
                            iban: null === current.attributes.iban ? '' : current.attributes.iban.match(/.{1,4}/g).join(' '),
                            account_number: null === current.attributes.account_number ? '' : current.attributes.account_number,
                            current_balance: current.attributes.current_balance,
                            currency_code: current.attributes.currency_code,
                            native_current_balance: current.attributes.native_current_balance,
                            native_currency_code: current.attributes.native_currency_code,
                            last_activity: null === current.attributes.last_activity ? '' : format(new Date(current.attributes.last_activity), i18next.t('config.month_and_day_fns')),
                            balance_difference: current.attributes.balance_difference,
                            native_balance_difference: current.attributes.native_balance_difference,
                            liability_type: current.attributes.liability_type,
                            liability_direction: current.attributes.liability_direction,
                            interest: current.attributes.interest,
                            interest_period: current.attributes.interest_period,
                            current_debt: current.attributes.current_debt,
                        };

                        // get group info:
                        let groupId = current.attributes.object_group_id;
                        if(!this.pageOptions.groupedAccounts) {
                            groupId = '0';
                        }
                        if (!groupedAccounts.hasOwnProperty(groupId)) {
                            groupedAccounts[groupId] = {
                                group: {
                                    id: '0' === groupId || null === groupId ? null : parseInt(groupId),
                                    title: current.attributes.object_group_title, // are ignored if group id is null.
                                    order: current.attributes.object_group_order,
                                },
                                accounts: [],
                            }
                        }
                        groupedAccounts[groupId].accounts.push(account);

                        //this.accounts.push(account);
                    }
                }
                // order grouped accounts by order.
                let sortable = [];
                for (let set in groupedAccounts) {
                    sortable.push(groupedAccounts[set]);
                }
                sortable.sort(function(a, b) {
                    return a.group.order - b.group.order;
                });
                this.accounts = sortable;
                this.notifications.wait.show = false;
                this.pageOptions.isLoading = false;
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


    Alpine.magic("t", (el) => {
        return (name, vars) => {
            return i18next.t(name, vars);
        };
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
