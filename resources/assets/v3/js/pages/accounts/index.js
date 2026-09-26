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
import Alpine from "alpinejs";
import { getVariable } from "../../store/get-variable.js";
import Put from "../../api/model/account/put.js";
import Get from "../../api/model/account/get.js";
import { format } from "date-fns";
import formatMoney from "../../util/format-money.js";
import i18next from "i18next";
import { addDrag } from "../shared/drag-and-droppable-rows.js";
import { nextTick } from "alpinejs/src/nextTick.js";

window.enableDates = false;

let index = function () {
    return {
        listPageSize: 50,
        objectType: "invalid",
        sortColumn: "order",
        accounts: [],
        sortDirection: "asc",
        page: 1,
        totalPages: 1,
        loadingPage: true,
        loadingNewSort: true,
        active: true,
        pageNavUrl: "./accounts/",
        handleFunc: null,
        storageKey: "",
        sums: {},
        debts: {},
        differences: {},
        formatMoney: formatMoney,

        updateHistory() {
            if (history.pushState) {
                let newUrl =
                    window.location.protocol +
                    "//" +
                    window.location.host +
                    window.location.pathname +
                    "?page=" +
                    this.page +
                    "&column=" +
                    this.sortColumn +
                    "&direction=" +
                    this.sortDirection;
                window.history.pushState({ path: newUrl }, "", newUrl);
                window.store.set(this.storageKey, { column: this.sortColumn, direction: this.sortDirection });
            }
        },
        init() {
            this.handleFunc = this.handlePageClick.bind(this);
            const page = window.location.href.split("?")[0].split("/");
            if ("inactive-accounts" === page[page.length - 2]) {
                this.active = false;
            }
            let defaultSortColumn = "order";
            let defaultSortDirection = "asc";
            this.objectType = page[page.length - 1].substring(0, 15);
            this.storageKey = "accounts-" + this.objectType + (this.active ? "-active" : "-inactive");
            this.pageNavUrl = "./accounts/" + this.objectType;
            const params = new Proxy(new URLSearchParams(window.location.search), {
                get: (searchParams, prop) => searchParams.get(prop),
            });

            if ("expense" === this.objectType || "revenue" === this.objectType) {
                defaultSortColumn = "name";
            }
            let fromStore = window.store.get(this.storageKey);
            console.log("from store", fromStore);
            if (fromStore) {
                defaultSortColumn = fromStore.column;
                defaultSortDirection = fromStore.direction;
            }

            this.sortColumn = params.column ?? defaultSortColumn;
            this.sortDirection = params.direction ?? defaultSortDirection;
            this.page = parseInt(params.page) || 1;

            // grab the account list.
            this.downloadAccounts();
            // get accounts by initial sort.
            document.addEventListener("alpine:initialized", () => {
                document.querySelectorAll("table.sortable th.sortable").forEach((el) => {
                    el.addEventListener("click", (event) => {
                        let newColumn = event.currentTarget.dataset.column;
                        if (newColumn === this.sortColumn) {
                            this.sortDirection = "asc" === this.sortDirection ? "desc" : "asc";
                        }
                        if (newColumn !== this.sortColumn) {
                            this.sortColumn = newColumn;
                        }
                        console.log("New sort instructions", this.sortColumn, this.sortDirection);
                        this.updateHistory();
                        this.downloadAccounts();
                    });
                });
            });
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
        downloadAccounts() {
            this.loadingNewSort = true;
            let sort = "asc" === this.sortDirection ? this.sortColumn : "-" + this.sortColumn;
            let start = window.store.get("start");
            let end = window.store.get("end");
            let convertToPrimary = window.store.get("convert_to_primary");
            new Get()
                .list({
                    active: this.active,
                    sort: sort,
                    page: this.page,
                    type: this.objectType,
                    start: start,
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
                            let current = response.data.data[i];

                            // collect sums, debts and differences for each account (in primary or not):
                            this.sums[current.attributes.currency_code] =
                                this.sums[current.attributes.currency_code] || 0;
                            this.sums[current.attributes.primary_currency_code] =
                                this.sums[current.attributes.primary_currency_code] || 0;
                            this.debts[current.attributes.currency_code] =
                                this.debts[current.attributes.currency_code] || 0;
                            this.debts[current.attributes.primary_currency_code] =
                                this.debts[current.attributes.primary_currency_code] || 0;
                            this.differences[current.attributes.currency_code] =
                                this.differences[current.attributes.currency_code] || 0;
                            this.differences[current.attributes.primary_currency_code] =
                                this.differences[current.attributes.primary_currency_code] || 0;

                            let balanceDifference = formatMoney(
                                current.attributes.balance_difference,
                                current.attributes.currency_code,
                                true,
                            );
                            let balanceDiffFloat = parseFloat(current.attributes.balance_difference);

                            let currentBalance = formatMoney(
                                current.attributes.current_balance,
                                current.attributes.currency_code,
                            );
                            let currentBalanceFloat = parseFloat(current.attributes.current_balance);

                            let currentDebt = formatMoney(
                                current.attributes.debt_amount,
                                current.attributes.currency_code,
                            );
                            let currentDebtFloat = parseFloat(current.attributes.debt_amount);

                            // this.sums[current.attributes.currency_code] += currentBalanceFloat;
                            if (!convertToPrimary) {
                                this.sums[current.attributes.currency_code] += currentBalanceFloat;
                                this.debts[current.attributes.currency_code] += currentDebtFloat;
                                this.differences[current.attributes.currency_code] += balanceDiffFloat;
                            }
                            if (convertToPrimary) {
                                balanceDifference = formatMoney(
                                    current.attributes.pc_balance_difference,
                                    current.attributes.primary_currency_code,
                                    true,
                                );
                                balanceDiffFloat = parseFloat(current.attributes.pc_balance_difference);

                                currentBalance = formatMoney(
                                    current.attributes.pc_current_balance,
                                    current.attributes.primary_currency_code,
                                );
                                currentBalanceFloat = parseFloat(current.attributes.pc_current_balance);

                                currentDebt = formatMoney(
                                    current.attributes.pc_debt_amount,
                                    current.attributes.primary_currency_code,
                                );
                                currentDebtFloat = parseFloat(current.attributes.pc_debt_amount);

                                this.sums[current.attributes.primary_currency_code] += currentBalanceFloat;
                                this.debts[current.attributes.primary_currency_code] += currentDebtFloat;
                                this.differences[current.attributes.primary_currency_code] += balanceDiffFloat;
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
                                current_balance: currentBalance,
                                current_balance_float: currentBalanceFloat,
                                active: current.attributes.active,
                                last_activity: lastActivity,
                                no_last_activity: noLastActivity,
                                balance_difference: balanceDifference,
                                balance_difference_float: balanceDiffFloat,
                                liability_type: i18next.t("firefly.account_type_" + current.attributes.liability_type),
                                liability_direction: i18next.t(
                                    "firefly.liability_direction_" + current.attributes.liability_direction + "_short",
                                ),
                                liability_interest: current.attributes.interest,
                                liability_interest_period: i18next
                                    .t("firefly.interest_calc_" + current.attributes.interest_period)
                                    .toLowerCase(),
                                current_debt: currentDebt,
                                current_debt_float: currentDebtFloat,
                            };
                            this.accounts.push(account);
                        }
                    }
                    this.loadingPage = false;
                    this.loadingNewSort = false;
                    if (0 === this.accounts.length) {
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
            return format(new Date(date), i18next.t("config.date_time_fns_short", { lng: window.store.get("locale") }));
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
            nextTick(() => {
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
