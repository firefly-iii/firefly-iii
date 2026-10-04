/*
 * show.js
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

import "../../boot/bootstrap.js";
import sidebar from "../shared/sidebar.js";
import dates from "../shared/dates.js";
import Alpine from "@alpinejs/csp";
import { getVariable } from "../../store/get-variable.js";
import Put from "../../api/model/account/put.js";
import Get from "../../api/model/account/get.js";
import format from "../../util/format.js";
import formatMoney from "../../util/format-money.js";
import i18next from "i18next";
import { addDrag } from "../shared/drag-and-droppable-rows.js";
import {sortableTable} from "../shared/sortable-tables.js";

window.enableDates = true;

let index = function () {
    return {
        listPageSize: 50,
        objectType: "invalid",
        accounts: [],
        page: 1,
        i18next: null,
        anonymous: false,
        totalPages: 1,
        loadingPage: true,
        convertToPrimary: false,
        active: true,
        storageKey: "",
        sums: {},
        debts: {},
        differences: {},

        // functions
        formatMoney: formatMoney,

        // default settings for sorting
        defaultSortColumn: 'order',
        defaultSortDirection: 'asc',

        // variables necessary for sorting tables.
        handleFunc: null,
        pageNavUrl: "./accounts/",
        sortColumn: "order",
        sortDirection: "asc",
        loadingNewSort: true,
        isFiltering: false,
        filter: {},

        // sort functions
        sortableTable: null,

        objectToQueryString(obj, prefix) {
            if (null === obj) {
                return "";
            }
            return Object.keys(obj)
                .map((objKey) => {
                    if (Object.hasOwn(obj, objKey)) {
                        const key = prefix ? `${prefix}[${objKey}]` : objKey;
                        const value = obj[objKey];

                        return typeof value === "object"
                            ? this.objectToQueryString(value, key)
                            : `${encodeURIComponent(key)}=${encodeURIComponent(value)}`;
                    }

                    return null;
                })
                .join("&");
        },

        updateHistory() {
            if (history.pushState) {
                // TODO fix this.
                let obj = {
                    page: this.page,
                    column: this.sortColumn,
                    direction: this.sortDirection,
                    filter: this.filter,
                };
                let string = this.objectToQueryString(obj);
                let newUrl =
                    window.location.protocol + "//" + window.location.host + window.location.pathname + "?" + string;
                window.history.pushState({ path: newUrl }, "", newUrl);
                window.store.set(this.storageKey, { column: this.sortColumn, direction: this.sortDirection });
            }
        },
        updateFilterValue(field, newValue) {
            console.log("Update", field, newValue);
            this.filter[field] = newValue;
            if ("" !== newValue) {
                this.isFiltering = true;
            }
            if ("" === newValue) {
                this.isFiltering = false;
            }
            this.updateHistory();
            this.downloadAccounts();
        },

        init() {
            const page = window.location.href.split("?")[0].split("/");
            this.objectType = page[page.length - 1].substring(0, 15);
            this.i18next = i18next;
            this.anonymous = window.store.get("anonymous");
            this.storageKey = "accounts-" + this.objectType + (this.active ? "-active" : "-inactive");
            this.handleFunc = this.handlePageClick.bind(this);
            this.pageNavUrl = "./accounts/" + this.objectType;

            if ("expense" === this.objectType || "revenue" === this.objectType) {
                this.defaultSortColumn = "name";
            }

            // sort and paging settings.
            if ("inactive-accounts" === page[page.length - 2]) {
                this.active = false;
            }

            let fromStore = window.store.get(this.storageKey);
            console.log("from store", fromStore);
            if (fromStore) {
                this.defaultSortColumn = fromStore.column;
                this.defaultSortDirection = fromStore.direction;
            }

            // document.querySelectorAll('table[data-sort-identifier="main"] thead th[data-filter-column]').forEach((th) => {
            //     let column = th.getAttribute('data-filter-column');
            //     console.log('[index] Will watch filter for column "' + column + '"');
            //     this.$watch('filter.' + column, (newValue, oldValue) => {
            //         console.log('filter for column "' + column + '" changed from "' + oldValue + '" to "' + newValue + '"');
            //     });
            // });


            const params = new Proxy(new URLSearchParams(window.location.search), {
                get: (searchParams, prop) => searchParams.get(prop),
            });
            this.sortColumn = params.column ?? this.defaultSortColumn;
            this.sortDirection = params.direction ?? this.defaultSortDirection;
            this.page = parseInt(params.page) || 1;

            this.sortableTable = new sortableTable('main');
            this.sortableTable.sortColumn = params.column ?? this.defaultSortColumn;
            this.sortableTable.sortDirection = params.direction ?? this.defaultSortDirection;
            this.sortableTable.page = this.page;
            this.sortableTable.init(this);

            // watch for changes in the sortable table instructions.
            document.addEventListener('sortable-table-sort-change', (event) => {
                console.log('sortable-table-sort-change', event.detail);
                this.sortColumn = event.detail.sortColumn;
                this.sortDirection = event.detail.sortDirection;
                // this.page = event.detail.page;
                this.updateHistory();
                this.downloadAccounts();
            });

            // grab the account list.
            this.downloadAccounts();



            getVariable("listPageSize").then((listPageSize) => {
                this.listPageSize = listPageSize;
                addDrag();
                document.addEventListener("firefly-iii-drag-complete", (e) => {
                    for (let i = 0; i < e.detail.length; i++) {
                        if (Object.hasOwn(e.detail, i)) {
                            let item = e.detail[i];
                            if (item.order !== item.currentOrder) {
                                // PUT new order to system.
                                new Put().put({ order: item.order }, { id: item.id });
                                // save new order as current order in the row.
                                document
                                    .querySelector(`tr[data-id="${item.id}"]`)
                                    .setAttribute("data-current-order", item.order);
                            }
                        }
                    }
                });
            });
        },
        prepareSumArrays(code) {
            let keys = ["sums", "differences", "debts"];
            for (let i = 0; i < keys.length; i++) {
                if (Object.hasOwn(this, keys[i])) {
                    this[keys[i]][code] = this[keys[i]][code] || 0;
                }
            }
        },
        filterAmounts(account) {
            // overrule some amounts, set them to zero when "this.anonymous" is true.
            if (this.anonymous) {
                account.attributes.balance_difference = "0";
                account.attributes.current_balance = "0";
                account.attributes.debt_amount = "0";
                account.attributes.pc_balance_difference = "0";
                account.attributes.pc_current_balance = "0";
                account.attributes.pc_debt_amount = "0";
            }
            return account;
        },
        createParsedAndFloatingAmounts(account, code, prefix) {
            let keys = ["balance_difference", "current_balance", "debt_amount"];
            for (let i = 0; i < keys.length; i++) {
                let key = keys[i];
                account.attributes[prefix + key + "_formatted"] = formatMoney(account.attributes[key], code, true);
                account.attributes[prefix + key + "_float"] = parseFloat(account.attributes[key]);
            }
            return account;
        },
        downloadAccounts() {
            this.loadingNewSort = true;
            let sort = "asc" === this.sortDirection ? this.sortColumn : "-" + this.sortColumn;
            let start = window.store.get("start");
            let end = window.store.get("end");
            this.convertToPrimary = window.store.get("convert_to_primary");
            new Get()
                .list({
                    active: this.active,
                    sort: sort,
                    page: this.page,
                    type: this.objectType,
                    start: start,
                    filter: this.filter,
                    end: end,
                })
                .then((response) => {
                    this.accounts = [];
                    this.sums = {};
                    this.debts = {};
                    this.differences = {};

                    this.totalPages = parseInt(response.data.meta.pagination.total_pages);
                    for (let i = 0; i < response.data.data.length; i++) {
                        if (Object.hasOwn(response.data.data, i)) {
                            let current = this.filterAmounts(response.data.data[i]);
                            let cc = current.attributes.currency_code;
                            let pcc = current.attributes.primary_currency_code;
                            this.prepareSumArrays(cc);
                            this.prepareSumArrays(pcc);

                            // add formatted amount property.
                            current = this.createParsedAndFloatingAmounts(current, cc, "");
                            current = this.createParsedAndFloatingAmounts(current, pcc, "pc_");

                            if (!this.convertToPrimary) {
                                this.sums[cc] += current.attributes.current_balance_float;
                                this.debts[cc] += current.attributes.debt_amount_float;
                                this.differences[cc] += current.attributes.balance_difference_float;
                            }
                            if (this.convertToPrimary) {
                                this.sums[pcc] += current.attributes.pc_current_balance_float;
                                this.debts[pcc] += current.attributes.pc_debt_amount_float;
                                this.differences[pcc] += current.attributes.pc_balance_difference_float;
                            }
                            let lastActivity = this.formatDate(current.attributes.last_activity);
                            let noLastActivity = false;
                            if ("" === lastActivity) {
                                lastActivity = i18next.t("firefly.never");
                                noLastActivity = true;
                            }
                            let account = {
                                id: parseInt(current.id),
                                name: current.attributes.name,
                                location: null !== current.attributes.longitude && null !== current.attributes.latitude,
                                has_attachments: current.attributes.has_attachments,
                                role: current.attributes.account_role,
                                iban: this.addSpaces(current.attributes.iban),
                                account_number: current.attributes.account_number,

                                currency_id: parseInt(current.attributes.currency_id),
                                primary_currency_id: parseInt(current.attributes.primary_currency_id),

                                active: current.attributes.active,
                                last_activity: lastActivity,
                                no_last_activity: noLastActivity,

                                current_balance: current.attributes.current_balance_formatted,
                                current_balance_float: current.attributes.current_balance_float,
                                pc_current_balance: current.attributes.pc_current_balance_formatted,
                                pc_current_balance_float: current.attributes.pc_current_balance_float,

                                balance_difference: current.attributes.balance_difference_formatted,
                                balance_difference_float: current.attributes.balance_difference_float,
                                pc_balance_difference: current.attributes.pc_balance_difference_formatted,
                                pc_balance_difference_float: current.attributes.pc_balance_difference_float,

                                current_debt: current.attributes.debt_amount_formatted,
                                current_debt_float: current.attributes.debt_amount_float,
                                pc_current_debt: current.attributes.pc_debt_amount_formatted,
                                pc_current_debt_float: current.attributes.pc_debt_amount_float,

                                liability_type: i18next.t("firefly.account_type_" + current.attributes.liability_type),
                                liability_direction: i18next.t(
                                    "firefly.liability_direction_" + current.attributes.liability_direction + "_short",
                                ),
                                liability_interest: current.attributes.interest,
                                liability_interest_period: i18next
                                    .t("firefly.interest_calc_" + current.attributes.interest_period)
                                    .toLowerCase(),
                            };
                            this.accounts.push(account);
                        }
                    }
                    this.loadingPage = false;
                    this.loadingNewSort = false;
                    if (0 === this.accounts.length && false === this.isFiltering) {
                        document.querySelectorAll(".data-holder").forEach((el) => {
                            el.classList.add("d-none");
                        });
                    }
                });
        },
        addSpaces(iban) {
            if (null === iban) {
                return "";
            }
            return iban.match(/.{1,4}/g).join(" ");
        },
        formatDate(date) {
            if (null === date) {
                return "";
            }
            return format(
                new Date(date),
                i18next.t("config.date_time_fns_short", { lng: window.store.get("locale") }),
                window.store.get("locale"),
            );
        },
        handlePageClick(e) {
            let link = e.currentTarget;

            let page = parseInt(link.dataset.page);
            if (isNaN(page)) {
                e.preventDefault();
                return false;
            }
            this.page = page;
            this.updateHistory();
            this.downloadAccounts();
            e.preventDefault();
            queueMicrotask(() => {
                this.capturePageNavigation();
            });
            return false;
        },
        capturePageNavigation() {
            document.querySelectorAll("a.page-link").forEach((el) => {
                el.removeEventListener("click", this.handleFunc);
                el.addEventListener("click", this.handleFunc);
            });
        },
    };
};

const comps = {
    index,
    sidebar,
    dates,
};

function loadPage(comps) {
    Object.keys(comps).forEach((comp) => {
        let data = comps[comp]();
        Alpine.data(comp, () => data);
    });
    Alpine.start();
}

// wait for load until bootstrapped event is received.
document.addEventListener("firefly-iii-bootstrapped", () => {
    loadPage(comps);
});
// or is bootstrapped before event is triggered.
if (window.bootstrapped) {
    loadPage(comps);
}
