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
        pageNavUrl: './accounts/',
        init() {
            const page = window.location.href.split("?")[0].split("/");
            this.objectType = page[page.length - 1].substring(0, 15);
            this.pageNavUrl = './accounts/' + this.objectType;
            const params = new Proxy(new URLSearchParams(window.location.search), {
                get: (searchParams, prop) => searchParams.get(prop),
            });
            this.sortColumn = params.column ?? "order";
            this.sortDirection = params.direction ?? "asc";
            this.page = parseInt(params.page) || 1;

            // grab the account list.
            this.downloadAccounts();

            // get accounts by initial sort.
            document.querySelectorAll("table.sortable th").forEach((el) => {
                // console.log('El', el);
                el.addEventListener("click", (event) => {
                    let newColumn = event.currentTarget.dataset.column;
                    if (newColumn === this.sortColumn) {
                        this.sortDirection = "asc" === this.sortDirection ? "desc" : "asc";
                    }
                    if (newColumn !== this.sortColumn) {
                        this.sortColumn = newColumn;
                    }
                    console.log("Will now sort on column", newColumn, "direction", this.sortDirection);
                    if (history.pushState) {
                        let newurl =
                            window.location.protocol +
                            "//" +
                            window.location.host +
                            window.location.pathname +
                            "?column=" +
                            this.sortColumn +
                            "&direction=" +
                            this.sortDirection;
                        window.history.pushState({ path: newurl }, "", newurl);
                    }
                    this.downloadAccounts();
                });
            });

            getVariable("listPageSize").then((listPageSize) => {
                this.listPageSize = listPageSize;
                //addDrag();
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
                    // console.log(e.detail);
                });
            });
        },
        downloadAccounts() {
            let sort = "asc" === this.sortDirection ? this.sortColumn : "-" + this.sortColumn;
            let start = window.store.get("start");
            let end = window.store.get("end");
            let active = true;
            new Get()
                .list({
                    active: active,
                    sort: sort,
                    page: this.page,
                    type: this.objectType,
                    start: start,
                    end: end,
                })
                .then((response) => {
                    this.accounts = [];
                    this.totalPages = parseInt(response.data.meta.pagination.total_pages);
                    for (let i = 1; i < response.data.data.length; i++) {
                        if (Object.hasOwn(response.data.data, i)) {
                            let current = response.data.data[i];
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
                            if (window.store.get("convert_to_primary")) {
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
                            }
                            let lastActivity = this.formatDate(current.attributes.last_activity);
                            let noLastActivity = false;
                            if ("" === lastActivity) {
                                lastActivity = i18next.t("firefly.never");
                                noLastActivity = true;
                            }
                            //console.log(current);
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
    };
};

const comps = {
    index,
    sidebar,
    dates,
};

function loadPage(comps) {
    // console.log('loadPage');
    Object.keys(comps).forEach((comp) => {
        let data = comps[comp]();
        Alpine.data(comp, () => data);
        // console.log(comp);
    });
    Alpine.start();
}

// wait for load until bootstrapped event is received.
document.addEventListener("firefly-iii-bootstrapped", () => {
    // console.log('Loaded through event listener.');
    loadPage(comps);
});
// or is bootstrapped before event is triggered.
if (window.bootstrapped) {
    // console.log('Loaded through window variable.');
    loadPage(comps);
}
